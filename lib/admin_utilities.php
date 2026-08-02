<?php
declare(strict_types=1);

const RANKING_APP_VERSION = '3.9';
const RANKING_BACKUP_FORMAT = 'ranking-backup-jsonl';
const RANKING_BACKUP_FORMAT_VERSION = 1;
const RANKING_BACKUP_EXTENSION = '.ranking-backup.jsonl';
const RANKING_IMPORT_JOB_TTL_SECONDS = 172800;

final class RankingAdminException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 400)
    {
        parent::__construct($message);
    }
}

final class RankingFileResponse
{
    public function __construct(
        public readonly string $path,
        public readonly string $downloadName,
        public readonly string $contentType,
        public readonly bool $deleteAfterSend = false
    ) {
    }
}

final class RankingMaintenanceLock
{
    /** @var resource|null */
    private $handle = null;

    public function __construct(private readonly string $path)
    {
        rankingEnsureDirectory(dirname($path));
        $handle = fopen($path, 'c');
        if ($handle === false) {
            throw new RankingAdminException('No se ha podido abrir el bloqueo de mantenimiento.', 500);
        }
        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            throw new RankingAdminException('Hay una operación de mantenimiento o importación en curso.', 409);
        }
        $this->handle = $handle;
        ftruncate($this->handle, 0);
        fwrite($this->handle, json_encode(['pid' => getmypid(), 'locked_at' => gmdate('c')], JSON_UNESCAPED_UNICODE) . PHP_EOL);
    }

    public function release(): void
    {
        if ($this->handle !== null) {
            flock($this->handle, LOCK_UN);
            fclose($this->handle);
            $this->handle = null;
        }
    }

    public function __destruct()
    {
        $this->release();
    }
}

function rankingRootPath(): string
{
    return dirname(__DIR__);
}

function rankingStoragePath(string $child = ''): string
{
    return rankingRootPath() . '/storage' . ($child === '' ? '' : '/' . trim($child, '/'));
}

function rankingEnsureDirectory(string $path): void
{
    if (is_dir($path)) {
        return;
    }
    if (!mkdir($path, 0750, true) && !is_dir($path)) {
        throw new RankingAdminException('No se ha podido crear el directorio de almacenamiento.', 500);
    }
}

function rankingJsonEncode(array $value): string
{
    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}

function rankingDataTables(): array
{
    return [
        'usuarios' => ['label' => 'Usuarios', 'backup' => true, 'clearable' => true],
        'app_settings' => ['label' => 'Configuración', 'backup' => true, 'clearable' => false],
        'atletas' => ['label' => 'Atletas', 'backup' => true, 'clearable' => true],
        'pruebas' => ['label' => 'Pruebas', 'backup' => true, 'clearable' => true],
        'ciudades' => ['label' => 'Ciudades', 'backup' => true, 'clearable' => true],
        'pistas' => ['label' => 'Pistas', 'backup' => true, 'clearable' => true],
        'usuario_atleta_permisos' => ['label' => 'Permisos', 'backup' => true, 'clearable' => true],
        'traducciones' => ['label' => 'Traducciones', 'backup' => true, 'clearable' => true],
        'marcas' => ['label' => 'Marcas', 'backup' => true, 'clearable' => true],
        'marcas_borradas' => ['label' => 'Papelera / marcas borradas', 'backup' => true, 'clearable' => true],
    ];
}

function rankingBackupTableNames(): array
{
    return array_keys(array_filter(rankingDataTables(), static fn(array $spec): bool => !empty($spec['backup'])));
}

function rankingDeleteOrder(): array
{
    return ['marcas', 'marcas_borradas', 'usuario_atleta_permisos', 'pistas', 'traducciones', 'atletas', 'pruebas', 'ciudades', 'usuarios', 'app_settings'];
}

function rankingInsertOrder(): array
{
    return ['usuarios', 'app_settings', 'atletas', 'pruebas', 'ciudades', 'pistas', 'usuario_atleta_permisos', 'traducciones', 'marcas', 'marcas_borradas'];
}

function rankingQuoteIdentifier(string $name): string
{
    if (!array_key_exists($name, rankingDataTables()) && $name !== 'schema_migrations') {
        throw new RankingAdminException('Tabla no permitida.', 500);
    }
    return '`' . str_replace('`', '``', $name) . '`';
}

function rankingTableColumns(PDO $db, string $table): array
{
    $statement = $db->query('SHOW COLUMNS FROM ' . rankingQuoteIdentifier($table));
    return array_map(static fn(array $row): string => (string) $row['Field'], $statement->fetchAll(PDO::FETCH_ASSOC));
}

function rankingPrimaryColumns(PDO $db, string $table): array
{
    $statement = $db->query('SHOW KEYS FROM ' . rankingQuoteIdentifier($table) . " WHERE Key_name = 'PRIMARY'");
    $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
    usort($rows, static fn(array $a, array $b): int => (int) $a['Seq_in_index'] <=> (int) $b['Seq_in_index']);
    return array_map(static fn(array $row): string => (string) $row['Column_name'], $rows);
}

function rankingSchemaMigrations(PDO $db): array
{
    $rows = $db->query('SELECT version FROM schema_migrations ORDER BY version')->fetchAll(PDO::FETCH_COLUMN);
    return array_map('strval', $rows);
}

function rankingTableExists(PDO $db, string $table): bool
{
    $statement = $db->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $statement->execute([$table]);
    return (bool) $statement->fetchColumn();
}

function rankingSetting(PDO $db, string $name, string $default = ''): string
{
    if (!rankingTableExists($db, 'app_settings')) {
        return $default;
    }
    $statement = $db->prepare('SELECT value FROM app_settings WHERE name = ?');
    $statement->execute([$name]);
    $value = $statement->fetchColumn();
    return $value === false ? $default : (string) $value;
}

function rankingSetSetting(PDO $db, string $name, string $value): void
{
    if (!rankingTableExists($db, 'app_settings')) {
        throw new RankingAdminException('Falta la tabla de configuración. Ejecuta las migraciones.', 500);
    }
    $statement = $db->prepare('INSERT INTO app_settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)');
    $statement->execute([$name, $value]);
}

function rankingCitiesSeedEnabled(PDO $db): bool
{
    return rankingSetting($db, 'seed_cities_enabled', '1') !== '0';
}

function rankingSetCitiesSeedEnabled(PDO $db, bool $enabled): void
{
    rankingSetSetting($db, 'seed_cities_enabled', $enabled ? '1' : '0');
}

function rankingMaintenanceLockPath(): string
{
    return rankingStoragePath('locks/maintenance.lock');
}

function rankingAcquireMaintenanceLock(): RankingMaintenanceLock
{
    return new RankingMaintenanceLock(rankingMaintenanceLockPath());
}

function rankingAuditEvent(?array $actor, string $action, string $result, array $details = []): void
{
    $path = rankingStoragePath('audit/admin-events.ndjson');
    rankingEnsureDirectory(dirname($path));
    unset($details['password'], $details['currentPassword'], $details['backup_content']);
    $event = [
        'timestamp' => gmdate('c'),
        'user_id' => isset($actor['id']) ? (int) $actor['id'] : null,
        'username' => $actor['username'] ?? null,
        'action' => $action,
        'result' => $result,
        'details' => $details,
    ];
    file_put_contents($path, rankingJsonEncode($event) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function rankingBackupDirectory(): string
{
    $path = rankingStoragePath('backups');
    rankingEnsureDirectory($path);
    return $path;
}

function rankingBackupFilename(string $reason): string
{
    $label = preg_replace('/[^a-z0-9_-]+/', '-', strtolower($reason)) ?: 'manual';
    return 'ranking_backup_' . gmdate('Ymd_His') . '_' . $label . '_' . bin2hex(random_bytes(3)) . RANKING_BACKUP_EXTENSION;
}

function rankingBackupPath(string $filename): string
{
    if (!preg_match('/^ranking_backup_\d{8}_\d{6}_[a-z0-9_-]+_[a-f0-9]{6}\.ranking-backup\.jsonl$/', $filename)) {
        throw new RankingAdminException('Nombre de backup no válido.', 400);
    }
    $path = rankingBackupDirectory() . '/' . $filename;
    $realDirectory = realpath(rankingBackupDirectory());
    $realPath = realpath($path);
    if ($realDirectory === false || $realPath === false || !str_starts_with($realPath, $realDirectory . DIRECTORY_SEPARATOR)) {
        throw new RankingAdminException('El backup no existe.', 404);
    }
    return $realPath;
}

function rankingCreateBackup(PDO $db, ?array $actor, string $reason = 'manual'): array
{
    $directory = rankingBackupDirectory();
    $filename = rankingBackupFilename($reason);
    $path = $directory . '/' . $filename;
    $temporary = $path . '.tmp';
    $tables = rankingBackupTableNames();
    $columns = [];
    $tableCounts = [];
    foreach ($tables as $table) {
        $columns[$table] = rankingTableColumns($db, $table);
        $tableCounts[$table] = (int) $db->query('SELECT COUNT(*) FROM ' . rankingQuoteIdentifier($table))->fetchColumn();
    }

    $handle = fopen($temporary, 'wb');
    if ($handle === false) {
        throw new RankingAdminException('No se ha podido crear el archivo de backup.', 500);
    }

    $counts = array_fill_keys($tables, 0);
    $checksums = [];
    $contexts = [];
    foreach ($tables as $table) {
        $contexts[$table] = hash_init('sha256');
    }

    try {
        $header = [
            'type' => 'header',
            'format' => RANKING_BACKUP_FORMAT,
            'format_version' => RANKING_BACKUP_FORMAT_VERSION,
            'application_version' => RANKING_APP_VERSION,
            'created_at' => gmdate('c'),
            'reason' => $reason,
            'created_by' => $actor ? ['id' => (int) $actor['id'], 'username' => $actor['username'] ?? null] : null,
            'schema_migrations' => rankingSchemaMigrations($db),
            'tables' => array_map(static fn(string $table): array => ['name' => $table, 'columns' => $columns[$table], 'expected_count' => $tableCounts[$table]], $tables),
            'excluded_tables' => ['schema_migrations'],
        ];
        fwrite($handle, rankingJsonEncode($header) . PHP_EOL);

        foreach ($tables as $table) {
            $orderColumns = rankingPrimaryColumns($db, $table) ?: $columns[$table];
            $order = implode(', ', array_map(static fn(string $column): string => '`' . str_replace('`', '``', $column) . '`', $orderColumns));
            $statement = $db->query('SELECT * FROM ' . rankingQuoteIdentifier($table) . ' ORDER BY ' . $order);
            while (($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
                $canonical = rankingJsonEncode($row);
                hash_update($contexts[$table], $canonical . "\n");
                $counts[$table]++;
                fwrite($handle, rankingJsonEncode(['type' => 'row', 'table' => $table, 'data' => $row]) . PHP_EOL);
            }
        }

        foreach ($tables as $table) {
            $checksums[$table] = hash_final($contexts[$table]);
        }
        $footer = [
            'type' => 'footer',
            'completed_at' => gmdate('c'),
            'counts' => $counts,
            'checksums' => $checksums,
            'total_rows' => array_sum($counts),
        ];
        fwrite($handle, rankingJsonEncode($footer) . PHP_EOL);
        fclose($handle);
        if (!rename($temporary, $path)) {
            throw new RankingAdminException('No se ha podido finalizar el archivo de backup.', 500);
        }
        return ['filename' => $filename, 'path' => $path, 'header' => $header, 'footer' => $footer];
    } catch (Throwable $exception) {
        if (is_resource($handle)) {
            fclose($handle);
        }
        if (is_file($temporary)) {
            unlink($temporary);
        }
        throw $exception;
    }
}

function rankingReadBackupHeaderAndFooter(string $path): array
{
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        throw new RankingAdminException('No se ha podido leer el backup.', 500);
    }
    $header = null;
    $footer = null;
    $lineNumber = 0;
    while (($line = fgets($handle)) !== false) {
        $lineNumber++;
        $data = json_decode(trim($line), true);
        if (!is_array($data)) {
            continue;
        }
        if ($lineNumber === 1 && ($data['type'] ?? '') === 'header') {
            $header = $data;
        }
        if (($data['type'] ?? '') === 'footer') {
            $footer = $data;
        }
    }
    fclose($handle);
    return [$header, $footer];
}

function rankingListBackups(PDO $db): array
{
    $files = glob(rankingBackupDirectory() . '/*' . RANKING_BACKUP_EXTENSION) ?: [];
    $items = [];
    foreach ($files as $path) {
        $filename = basename($path);
        try {
            [$header, $footer] = rankingReadBackupHeaderAndFooter($path);
            $items[] = [
                'filename' => $filename,
                'createdAt' => $header['created_at'] ?? gmdate('c', filemtime($path) ?: time()),
                'size' => filesize($path) ?: 0,
                'formatVersion' => $header['format_version'] ?? null,
                'applicationVersion' => $header['application_version'] ?? null,
                'schemaMigrations' => $header['schema_migrations'] ?? [],
                'counts' => $footer['counts'] ?? [],
                'validSchema' => ($header['schema_migrations'] ?? []) === rankingSchemaMigrations($db),
            ];
        } catch (Throwable $exception) {
            $items[] = [
                'filename' => $filename,
                'createdAt' => gmdate('c', filemtime($path) ?: time()),
                'size' => filesize($path) ?: 0,
                'formatVersion' => null,
                'applicationVersion' => null,
                'schemaMigrations' => [],
                'counts' => [],
                'validSchema' => false,
                'error' => 'No se ha podido leer la metadata.',
            ];
        }
    }
    usort($items, static fn(array $a, array $b): int => strcmp((string) $b['createdAt'], (string) $a['createdAt']));
    return $items;
}

function rankingValidateBackup(PDO $db, string $path): array
{
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        throw new RankingAdminException('No se ha podido leer el backup.', 500);
    }
    $tables = rankingBackupTableNames();
    $expectedColumns = [];
    foreach ($tables as $table) {
        $expectedColumns[$table] = rankingTableColumns($db, $table);
    }
    $counts = array_fill_keys($tables, 0);
    $contexts = [];
    foreach ($tables as $table) {
        $contexts[$table] = hash_init('sha256');
    }
    $header = null;
    $footer = null;
    $lineNumber = 0;
    $activeAdmins = 0;
    $seenFooter = false;

    while (($line = fgets($handle)) !== false) {
        $lineNumber++;
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        try {
            $data = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            fclose($handle);
            throw new RankingAdminException('El backup contiene JSON no válido.', 400);
        }
        if (!is_array($data)) {
            fclose($handle);
            throw new RankingAdminException('El backup contiene una línea no válida.', 400);
        }
        $type = (string) ($data['type'] ?? '');
        if ($lineNumber === 1) {
            if ($type !== 'header') {
                fclose($handle);
                throw new RankingAdminException('El backup no contiene cabecera válida.', 400);
            }
            $header = $data;
            if (($header['format'] ?? '') !== RANKING_BACKUP_FORMAT || (int) ($header['format_version'] ?? 0) !== RANKING_BACKUP_FORMAT_VERSION) {
                fclose($handle);
                throw new RankingAdminException('La versión del formato de backup no es compatible.', 400);
            }
            if (($header['schema_migrations'] ?? []) !== rankingSchemaMigrations($db)) {
                fclose($handle);
                throw new RankingAdminException('El esquema del backup no coincide exactamente con el esquema actual.', 400);
            }
            $headerTables = [];
            foreach (($header['tables'] ?? []) as $tableSpec) {
                $headerTables[(string) ($tableSpec['name'] ?? '')] = $tableSpec;
            }
            if (array_keys($headerTables) !== $tables) {
                fclose($handle);
                throw new RankingAdminException('El backup no contiene la lista esperada de tablas.', 400);
            }
            foreach ($tables as $table) {
                if (($headerTables[$table]['columns'] ?? []) !== $expectedColumns[$table]) {
                    fclose($handle);
                    throw new RankingAdminException('Las columnas del backup no coinciden con el esquema actual.', 400);
                }
            }
            continue;
        }
        if ($seenFooter) {
            fclose($handle);
            throw new RankingAdminException('El backup contiene datos después del pie de verificación.', 400);
        }
        if ($type === 'footer') {
            $footer = $data;
            $seenFooter = true;
            continue;
        }
        if ($type !== 'row') {
            fclose($handle);
            throw new RankingAdminException('El backup contiene una línea de tipo desconocido.', 400);
        }
        $table = (string) ($data['table'] ?? '');
        if (!in_array($table, $tables, true)) {
            fclose($handle);
            throw new RankingAdminException('El backup contiene una tabla no permitida.', 400);
        }
        $row = $data['data'] ?? null;
        if (!is_array($row) || array_keys($row) !== $expectedColumns[$table]) {
            fclose($handle);
            throw new RankingAdminException('El backup contiene una fila con columnas no válidas.', 400);
        }
        if ($table === 'usuarios' && (string) ($row['rol'] ?? '') === 'admin' && (int) ($row['activo'] ?? 0) === 1) {
            $activeAdmins++;
        }
        $canonical = rankingJsonEncode($row);
        hash_update($contexts[$table], $canonical . "\n");
        $counts[$table]++;
    }
    fclose($handle);
    if ($header === null || $footer === null) {
        throw new RankingAdminException('El backup está incompleto.', 400);
    }
    $checksums = [];
    foreach ($tables as $table) {
        $checksums[$table] = hash_final($contexts[$table]);
    }
    if (($footer['counts'] ?? []) !== $counts || ($footer['checksums'] ?? []) !== $checksums || (int) ($footer['total_rows'] ?? -1) !== array_sum($counts)) {
        throw new RankingAdminException('Los checksums o recuentos del backup no cuadran.', 400);
    }
    if ($activeAdmins < 1) {
        throw new RankingAdminException('El backup no contiene ningún administrador activo.', 400);
    }
    return ['header' => $header, 'footer' => $footer, 'counts' => $counts];
}

function rankingRestoreBackup(PDO $db, array $actor, string $path): array
{
    $validation = rankingValidateBackup($db, $path);
    $preRestore = rankingCreateBackup($db, $actor, 'pre_restore');
    foreach (rankingDeleteOrder() as $table) {
        $db->exec('DELETE FROM ' . rankingQuoteIdentifier($table));
    }
    $statements = [];
    foreach (rankingInsertOrder() as $table) {
        $columns = rankingTableColumns($db, $table);
        $columnSql = implode(', ', array_map(static fn(string $column): string => '`' . str_replace('`', '``', $column) . '`', $columns));
        $placeholderSql = implode(', ', array_fill(0, count($columns), '?'));
        $statements[$table] = $db->prepare('INSERT INTO ' . rankingQuoteIdentifier($table) . " ({$columnSql}) VALUES ({$placeholderSql})");
    }

    $handle = fopen($path, 'rb');
    if ($handle === false) {
        throw new RankingAdminException('No se ha podido leer el backup.', 500);
    }
    while (($line = fgets($handle)) !== false) {
        $data = json_decode(trim($line), true);
        if (!is_array($data) || ($data['type'] ?? '') !== 'row') {
            continue;
        }
        $table = (string) $data['table'];
        if (!isset($statements[$table])) {
            continue;
        }
        $statements[$table]->execute(array_values($data['data']));
    }
    fclose($handle);
    return ['preRestoreBackup' => $preRestore['filename'], 'restoredCounts' => $validation['counts']];
}

function rankingBackupDownloadResponse(string $filename): RankingFileResponse
{
    $path = rankingBackupPath($filename);
    return new RankingFileResponse($path, $filename, 'application/x-ndjson; charset=utf-8');
}

function rankingDeleteBackup(string $filename): void
{
    $path = rankingBackupPath($filename);
    if (!unlink($path)) {
        throw new RankingAdminException('No se ha podido eliminar el backup.', 500);
    }
}

function rankingDataCounts(PDO $db): array
{
    $counts = [];
    foreach (rankingDataTables() as $table => $_spec) {
        $counts[$table] = (int) $db->query('SELECT COUNT(*) FROM ' . rankingQuoteIdentifier($table))->fetchColumn();
    }
    return $counts;
}

function rankingClearDefinitions(PDO $db, array $actor): array
{
    $counts = rankingDataCounts($db);
    $activeMarks = $counts['marcas'];
    $deletedMarks = $counts['marcas_borradas'];
    $permissions = $counts['usuario_atleta_permisos'];
    $tracks = $counts['pistas'];
    $markTrackRefs = (int) $db->query("SELECT COUNT(*) FROM marcas WHERE pista_id IS NOT NULL OR COALESCE(nombre_pista, '') <> ''")->fetchColumn();
    $deletedTrackRefs = (int) $db->query("SELECT COUNT(*) FROM marcas_borradas WHERE pista_id IS NOT NULL OR COALESCE(nombre_pista, '') <> ''")->fetchColumn();
    return [
        'marks' => ['label' => 'Marcas', 'table' => 'marcas', 'count' => $activeMarks, 'phrase' => 'BORRAR MARCAS', 'requiresPassword' => true, 'blocked' => []],
        'deleted_marks' => ['label' => 'Papelera / marcas borradas', 'table' => 'marcas_borradas', 'count' => $deletedMarks, 'phrase' => 'VACIAR PAPELERA', 'requiresPassword' => false, 'blocked' => []],
        'permissions' => ['label' => 'Permisos', 'table' => 'usuario_atleta_permisos', 'count' => $permissions, 'phrase' => 'VACIAR PERMISOS', 'requiresPassword' => false, 'blocked' => []],
        'athletes' => ['label' => 'Atletas', 'table' => 'atletas', 'count' => $counts['atletas'], 'phrase' => 'VACIAR ATLETAS', 'requiresPassword' => false, 'blocked' => array_values(array_filter([
            $activeMarks > 0 ? 'Primero deben borrarse las marcas activas.' : null,
            $deletedMarks > 0 ? 'Primero debe vaciarse la papelera de marcas.' : null,
            $permissions > 0 ? 'Primero deben vaciarse los permisos por atleta.' : null,
        ]))],
        'events' => ['label' => 'Pruebas', 'table' => 'pruebas', 'count' => $counts['pruebas'], 'phrase' => 'VACIAR PRUEBAS', 'requiresPassword' => false, 'blocked' => array_values(array_filter([
            $activeMarks > 0 ? 'Primero deben borrarse las marcas activas.' : null,
            $deletedMarks > 0 ? 'Primero debe vaciarse la papelera de marcas.' : null,
        ]))],
        'cities' => ['label' => 'Ciudades', 'table' => 'ciudades', 'count' => $counts['ciudades'], 'phrase' => 'VACIAR CIUDADES', 'requiresPassword' => false, 'blocked' => array_values(array_filter([
            $activeMarks > 0 ? 'Primero deben borrarse las marcas activas.' : null,
            $deletedMarks > 0 ? 'Primero debe vaciarse la papelera de marcas.' : null,
            $tracks > 0 ? 'Primero deben vaciarse las pistas.' : null,
        ]))],
        'tracks' => ['label' => 'Pistas', 'table' => 'pistas', 'count' => $tracks, 'phrase' => 'VACIAR PISTAS', 'requiresPassword' => false, 'blocked' => array_values(array_filter([
            $markTrackRefs > 0 ? 'Existen marcas activas con pista histórica asociada.' : null,
            $deletedTrackRefs > 0 ? 'Existen marcas borradas con pista histórica asociada.' : null,
        ]))],
        'users' => ['label' => 'Usuarios', 'table' => 'usuarios', 'count' => max(0, $counts['usuarios'] - 1), 'phrase' => 'VACIAR USUARIOS', 'requiresPassword' => true, 'blocked' => array_values(array_filter([
            $activeMarks > 0 ? 'Primero deben borrarse las marcas activas para no perder auditoría.' : null,
            $deletedMarks > 0 ? 'Primero debe vaciarse la papelera de marcas para no perder auditoría.' : null,
            $permissions > 0 ? 'Primero deben vaciarse los permisos.' : null,
        ]))],
        'translations' => ['label' => 'Traducciones', 'table' => 'traducciones', 'count' => $counts['traducciones'], 'phrase' => 'VACIAR TRADUCCIONES', 'requiresPassword' => false, 'blocked' => []],
    ];
}

function rankingUtilityStatus(PDO $db, array $actor): array
{
    return [
        'backups' => rankingListBackups($db),
        'data' => array_values(rankingClearDefinitions($db, $actor)),
        'counts' => rankingDataCounts($db),
        'fullReset' => ['phrase' => 'VACIAR TODO', 'requiresPassword' => true],
        'restore' => ['phrase' => 'RESTAURAR', 'requiresPassword' => true],
        'deleteBackup' => ['phrase' => 'ELIMINAR COPIA', 'requiresPassword' => false],
        'seedCitiesEnabled' => rankingCitiesSeedEnabled($db),
    ];
}

function rankingRequireConfirmation(array $payload, string $phrase): void
{
    if (trim((string) ($payload['confirmation'] ?? '')) !== $phrase) {
        throw new RankingAdminException('La frase de confirmación no coincide.', 400);
    }
}

function rankingRequireCurrentPassword(PDO $db, array $actor, array $payload): void
{
    $password = (string) ($payload['password'] ?? '');
    $statement = $db->prepare('SELECT password_hash FROM usuarios WHERE id = ? AND activo = 1');
    $statement->execute([(int) $actor['id']]);
    $hash = $statement->fetchColumn();
    if ($password === '' || $hash === false || !password_verify($password, (string) $hash)) {
        throw new RankingAdminException('La contraseña actual no es correcta.', 403);
    }
}

function rankingClearEntity(PDO $db, string $entity, array $actor): array
{
    $definitions = rankingClearDefinitions($db, $actor);
    if (!isset($definitions[$entity])) {
        throw new RankingAdminException('Operación de vaciado no permitida.', 404);
    }
    $definition = $definitions[$entity];
    if ($definition['blocked']) {
        throw new RankingAdminException(implode(' ', $definition['blocked']), 409);
    }
    $count = (int) $definition['count'];
    switch ($entity) {
        case 'marks':
            $statement = $db->prepare('DELETE FROM marcas');
            $statement->execute();
            return ['cleared' => $statement->rowCount(), 'backup' => null];
        case 'deleted_marks':
            $statement = $db->prepare('DELETE FROM marcas_borradas');
            $statement->execute();
            return ['cleared' => $statement->rowCount()];
        case 'permissions':
            $statement = $db->prepare('DELETE FROM usuario_atleta_permisos');
            $statement->execute();
            return ['cleared' => $statement->rowCount()];
        case 'athletes':
            $statement = $db->prepare('DELETE FROM atletas');
            $statement->execute();
            return ['cleared' => $statement->rowCount()];
        case 'events':
            $statement = $db->prepare('DELETE FROM pruebas');
            $statement->execute();
            return ['cleared' => $statement->rowCount()];
        case 'cities':
            $statement = $db->prepare('DELETE FROM ciudades');
            $statement->execute();
            rankingSetCitiesSeedEnabled($db, false);
            return ['cleared' => $statement->rowCount(), 'seedCitiesEnabled' => false];
        case 'tracks':
            $statement = $db->prepare('DELETE FROM pistas');
            $statement->execute();
            return ['cleared' => $statement->rowCount()];
        case 'users':
            $statement = $db->prepare('DELETE FROM usuarios WHERE id <> ?');
            $statement->execute([(int) $actor['id']]);
            $db->prepare("UPDATE usuarios SET activo = 1, rol = 'admin' WHERE id = ?")->execute([(int) $actor['id']]);
            return ['cleared' => $statement->rowCount()];
        case 'translations':
            $statement = $db->prepare('DELETE FROM traducciones');
            $statement->execute();
            return ['cleared' => $statement->rowCount()];
    }
    return ['cleared' => $count];
}

function rankingClearAllMarksWithBackup(PDO $db, array $actor): array
{
    $backup = rankingCreateBackup($db, $actor, 'pre_clear_marks');
    $statement = $db->prepare('DELETE FROM marcas');
    $statement->execute();
    return ['cleared' => $statement->rowCount(), 'backup' => $backup['filename']];
}

function rankingFullReset(PDO $db, array $actor): array
{
    $backup = rankingCreateBackup($db, $actor, 'pre_full_reset');
    $counts = [];
    foreach (['marcas', 'marcas_borradas', 'usuario_atleta_permisos', 'pistas', 'atletas', 'pruebas', 'ciudades', 'traducciones'] as $table) {
        $statement = $db->prepare('DELETE FROM ' . rankingQuoteIdentifier($table));
        $statement->execute();
        $counts[$table] = $statement->rowCount();
    }
    $statement = $db->prepare('DELETE FROM usuarios WHERE id <> ?');
    $statement->execute([(int) $actor['id']]);
    $counts['usuarios'] = $statement->rowCount();
    $db->prepare("UPDATE usuarios SET activo = 1, rol = 'admin' WHERE id = ?")->execute([(int) $actor['id']]);
    rankingSetCitiesSeedEnabled($db, false);
    return ['cleared' => $counts, 'backup' => $backup['filename'], 'preservedAdminId' => (int) $actor['id']];
}

function rankingJobDirectory(): string
{
    $path = rankingStoragePath('import-jobs');
    rankingEnsureDirectory($path);
    return $path;
}

function rankingJobId(): string
{
    return bin2hex(random_bytes(16));
}

function rankingJobPath(string $jobId): string
{
    if (!preg_match('/^[a-f0-9]{32}$/', $jobId)) {
        throw new RankingAdminException('Identificador de importación no válido.', 400);
    }
    return rankingJobDirectory() . '/' . $jobId . '.json';
}

function rankingWriteJob(array $job): void
{
    $job['updated_at'] = gmdate('c');
    $path = rankingJobPath((string) $job['job_id']);
    $temporary = $path . '.tmp';
    if (file_put_contents($temporary, rankingJsonEncode($job)) === false || !rename($temporary, $path)) {
        throw new RankingAdminException('No se ha podido actualizar el progreso de importación.', 500);
    }
}

function rankingReadJob(string $jobId): array
{
    $path = rankingJobPath($jobId);
    if (!is_file($path)) {
        throw new RankingAdminException('No se encuentra la importación indicada.', 404);
    }
    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data)) {
        throw new RankingAdminException('El estado de importación no es válido.', 500);
    }
    return $data;
}

function rankingCleanupJobs(): void
{
    foreach (glob(rankingJobDirectory() . '/*.json') ?: [] as $path) {
        $data = json_decode((string) file_get_contents($path), true);
        if (!is_array($data) || ($data['status'] ?? '') === 'RUNNING') {
            continue;
        }
        $updated = strtotime((string) ($data['updated_at'] ?? '')) ?: filemtime($path);
        if ($updated !== false && $updated < time() - RANKING_IMPORT_JOB_TTL_SECONDS) {
            unlink($path);
        }
    }
}

function rankingCreateImportJob(array $actor, int $total): array
{
    rankingCleanupJobs();
    $job = [
        'job_id' => rankingJobId(),
        'created_at' => gmdate('c'),
        'updated_at' => gmdate('c'),
        'user_id' => (int) $actor['id'],
        'phase' => 'PREPARING',
        'processed' => 0,
        'total' => max(0, $total),
        'percent' => 0,
        'status' => 'PENDING',
        'message' => 'Preparando importación.',
        'error' => null,
    ];
    rankingWriteJob($job);
    return $job;
}

function rankingUpdateJobProgress(array $job, string $phase, int $processed, ?int $total = null, string $message = '', string $status = 'RUNNING', ?string $error = null): array
{
    $job['phase'] = $phase;
    $job['processed'] = max(0, $processed);
    if ($total !== null) {
        $job['total'] = max(0, $total);
    }
    $job['percent'] = $job['total'] > 0 ? min(100, (int) floor(($job['processed'] / $job['total']) * 100)) : 0;
    $job['status'] = $status;
    $job['message'] = $message;
    $job['error'] = $error;
    rankingWriteJob($job);
    return $job;
}
