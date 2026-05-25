<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/env.php';

$root = dirname(__DIR__);
$directory = $root . '/database/migrations';
$envFile = dirname($root) . '/.env';

try {
    $db = databaseConnection($envFile);
    $trackingMigration = $directory . '/001_create_migration_tracking.sql';
    $count = 0;

    $tracking = $db->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $tracking->execute(['schema_migrations']);
    if (!$tracking->fetchColumn()) {
        $sql = file_get_contents($trackingMigration);
        if ($sql === false) {
            throw new RuntimeException('No se encuentra la migracion inicial de control.');
        }

        echo 'Aplicando 001_create_migration_tracking.sql...' . PHP_EOL;
        $db->exec($sql);
        $statement = $db->prepare('INSERT INTO schema_migrations (version) VALUES (?)');
        $statement->execute([basename($trackingMigration)]);
        $count++;
    }

    $applied = $db->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
    $files = glob($directory . '/*.sql') ?: [];
    sort($files, SORT_STRING);

    foreach ($files as $file) {
        $version = basename($file);
        if (in_array($version, $applied, true)) {
            continue;
        }

        $sql = file_get_contents($file);
        if ($sql === false) {
            throw new RuntimeException("No se ha podido leer la migracion {$version}.");
        }

        echo "Aplicando {$version}..." . PHP_EOL;
        $db->exec($sql);
        $statement = $db->prepare('INSERT INTO schema_migrations (version) VALUES (?)');
        $statement->execute([$version]);
        $count++;
    }

    if ($count === 0) {
        echo 'No hay migraciones pendientes.' . PHP_EOL;
    } else {
        echo sprintf('%d migracion(es) aplicada(s).', $count) . PHP_EOL;
    }
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        'No se han podido aplicar las migraciones: ' . $exception->getMessage() . PHP_EOL
    );
    exit(1);
}
