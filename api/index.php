<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/env.php';

session_name('ranking_session');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'path' => '/',
]);
session_start();

const USUAL_EVENTS = [
    '60 m', '80 m', '100 m', '200 m', '300 m', '400 m', '600 m', '800 m',
    '1.000 m', '1.500 m', '3.000 m', '5.000 m', '10.000 m', '60 m vallas',
    '100 m vallas', '110 m vallas', '400 m vallas', '3.000 m obstaculos',
    '4 x 100 m', '4 x 400 m', 'Altura', 'Longitud', 'Triple salto', 'Pertiga',
    'Peso', 'Disco', 'Jabalina', 'Martillo', 'Decatlon', 'Heptatlon',
];

const HIGHER_RESULT_EVENTS = [
    'Altura', 'Longitud', 'Triple salto', 'Pertiga', 'Peso', 'Disco', 'Jabalina', 'Martillo', 'Decatlon', 'Heptatlon',
];

final class ApiException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 400)
    {
        parent::__construct($message);
    }
}

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

function connection(): PDO
{
    return databaseConnection(dirname(__DIR__, 2) . '/.env');
}

function input(): array
{
    $content = file_get_contents('php://input');
    if ($content === '' || $content === false) {
        return [];
    }
    try {
        $value = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        throw new ApiException('La peticion contiene JSON no valido.');
    }
    if (!is_array($value)) {
        throw new ApiException('La peticion contiene JSON no valido.');
    }
    return $value;
}

function path(): string
{
    $uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '';
    $position = strpos($uriPath, '/api');
    if ($position === false) {
        return '/';
    }
    $apiPath = substr($uriPath, $position + 4);
    return $apiPath === '' ? '/' : $apiPath;
}

function requiredText(array $payload, string $key, string $label): string
{
    $value = trim((string) ($payload[$key] ?? ''));
    if ($value === '') {
        throw new ApiException("El campo {$label} es obligatorio.");
    }
    return $value;
}

function requiredDate(array $payload, string $key, string $label): string
{
    $value = requiredText($payload, $key, $label);
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $errors = DateTimeImmutable::getLastErrors();
    if (!$date || ($errors !== false && ($errors['warning_count'] || $errors['error_count'])) ||
        $date->format('Y-m-d') !== $value) {
        throw new ApiException("El campo {$label} debe tener formato AAAA-MM-DD.");
    }
    if ($date > new DateTimeImmutable('today')) {
        throw new ApiException("El campo {$label} no puede estar en el futuro.");
    }
    return $value;
}

function requiredId(array $payload, string $key, string $label): int
{
    $id = filter_var($payload[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id) {
        throw new ApiException("El campo {$label} no es valido.");
    }
    return $id;
}

function requiredResult(array $payload): string
{
    $result = requiredText($payload, 'result', 'resultado');
    $clean = preg_replace('/[^\d:.,]/', '', $result);
    if (!$clean || !preg_match('/^(?:\d+(?:[.,]\d+)?|(?:\d+:){1,2}\d+(?:[.,]\d+)?)$/', $clean)) {
        throw new ApiException('Indica una marca numerica, por ejemplo 12.43 o 1:58.20.');
    }
    return $result;
}

function requiredResultDirection(array $payload): string
{
    $direction = (string) ($payload['resultDirection'] ?? '');
    if (!in_array($direction, ['lower', 'higher'], true)) {
        throw new ApiException('Indica si en la prueba gana la marca menor o la mayor.');
    }
    return $direction;
}

function storedResultDirection(string $direction): string
{
    return $direction === 'higher' ? 'mayor' : 'menor';
}

function usualResultDirection(string $event): string
{
    return in_array($event, HIGHER_RESULT_EVENTS, true) ? 'higher' : 'lower';
}

function comparableResult(string $result): float
{
    $clean = str_replace(',', '.', preg_replace('/[^\d:,.]/', '', $result) ?? '');
    $chunks = array_map('floatval', explode(':', $clean));
    return match (count($chunks)) {
        3 => $chunks[0] * 3600 + $chunks[1] * 60 + $chunks[2],
        2 => $chunks[0] * 60 + $chunks[1],
        default => $chunks[0],
    };
}

function categoryForDates(string $birthdate, string $performanceDate): string
{
    $birth = new DateTimeImmutable($birthdate);
    $performance = new DateTimeImmutable($performanceDate);
    if ($performance < $birth) {
        throw new ApiException('La fecha de la marca no puede ser anterior al nacimiento.');
    }
    $age = $birth->diff($performance)->y;
    foreach ([
        8 => 'sub8', 10 => 'sub10', 12 => 'sub12', 14 => 'sub14',
        16 => 'sub16', 18 => 'sub18', 20 => 'sub20', 23 => 'sub23',
        35 => 'senior',
    ] as $limit => $category) {
        if ($age < $limit) {
            return $category;
        }
    }
    return 'master';
}

function execute(PDO $db, string $sql, array $parameters = []): PDOStatement
{
    $statement = $db->prepare($sql);
    $statement->execute($parameters);
    return $statement;
}

function requiredUsername(array $payload): string
{
    $username = strtolower(requiredText($payload, 'username', 'usuario'));
    if (!preg_match('/^[a-z0-9._-]{3,100}$/', $username)) {
        throw new ApiException('El usuario debe tener al menos 3 caracteres y solo usar letras, numeros, punto, guion o guion bajo.');
    }
    return $username;
}

function requiredPassword(array $payload, bool $optional = false): ?string
{
    $password = (string) ($payload['password'] ?? '');
    if ($optional && $password === '') {
        return null;
    }
    if (strlen($password) < 8) {
        throw new ApiException('La contrasena debe tener al menos 8 caracteres.');
    }
    return $password;
}

function currentUser(PDO $db): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $user = execute(
        $db,
        'SELECT id, nombre AS name, usuario AS username, activo AS active FROM usuarios WHERE id = ? AND activo = 1',
        [(int) $_SESSION['user_id']]
    )->fetch();
    if (!$user) {
        unset($_SESSION['user_id']);
        return null;
    }
    $user['active'] = (bool) $user['active'];
    return $user;
}

function requireUser(PDO $db): array
{
    $user = currentUser($db);
    if (!$user) {
        throw new ApiException('Debes iniciar sesion para continuar.', 401);
    }
    return $user;
}

function users(PDO $db): array
{
    $rows = execute(
        $db,
        'SELECT id, nombre AS name, usuario AS username, activo AS active FROM usuarios ORDER BY nombre, usuario'
    )->fetchAll();
    return array_map(static function (array $user): array {
        $user['active'] = (bool) $user['active'];
        return $user;
    }, $rows);
}

function createUser(PDO $db, array $payload, bool $forceActive = false): int
{
    $active = $forceActive || !array_key_exists('active', $payload) || (bool) $payload['active'];
    execute($db, 'INSERT INTO usuarios (nombre, usuario, password_hash, activo) VALUES (?, ?, ?, ?)', [
        requiredText($payload, 'name', 'nombre'),
        requiredUsername($payload),
        password_hash(requiredPassword($payload), PASSWORD_DEFAULT),
        $active ? 1 : 0,
    ]);
    return (int) $db->lastInsertId();
}

function updateUser(PDO $db, int $id, array $payload, array $actor): void
{
    ensureExists($db, 'usuarios', $id);
    $active = !array_key_exists('active', $payload) || (bool) $payload['active'];
    if ($id === (int) $actor['id'] && !$active) {
        throw new ApiException('No puedes desactivar tu propio usuario.');
    }
    $parameters = [requiredText($payload, 'name', 'nombre'), requiredUsername($payload), $active ? 1 : 0];
    $sql = 'UPDATE usuarios SET nombre = ?, usuario = ?, activo = ?';
    $password = requiredPassword($payload, true);
    if ($password !== null) {
        $sql .= ', password_hash = ?';
        $parameters[] = password_hash($password, PASSWORD_DEFAULT);
    }
    $sql .= ' WHERE id = ?';
    $parameters[] = $id;
    execute($db, $sql, $parameters);
}

function deleteUser(PDO $db, int $id, array $actor): void
{
    if ($id === (int) $actor['id']) {
        throw new ApiException('No puedes eliminar tu propio usuario.');
    }
    ensureExists($db, 'usuarios', $id);
    $activeUsers = (int) execute($db, 'SELECT COUNT(*) AS total FROM usuarios WHERE activo = 1')->fetch()['total'];
    $target = execute($db, 'SELECT activo FROM usuarios WHERE id = ?', [$id])->fetch();
    if ($target && (bool) $target['activo'] && $activeUsers <= 1) {
        throw new ApiException('Debe existir al menos un usuario activo.');
    }
    execute($db, 'DELETE FROM usuarios WHERE id = ?', [$id]);
}

function bootstrap(PDO $db): array
{
    $athletes = execute(
        $db,
        "SELECT id, nombre AS name, apellidos AS surname,
         DATE_FORMAT(fecha_nacimiento, '%Y-%m-%d') AS birthdate
         FROM atletas ORDER BY apellidos, nombre"
    )->fetchAll();
    $events = execute(
        $db,
        "SELECT id, nombre AS name, CASE sentido_resultado WHEN 'mayor' THEN 'higher' ELSE 'lower' END AS resultDirection FROM pruebas ORDER BY nombre"
    )->fetchAll();
    $tracks = execute(
        $db,
        'SELECT id, nombre AS name, localidad AS city FROM pistas ORDER BY nombre, localidad'
    )->fetchAll();
    $marks = execute(
        $db,
        "SELECT id, atleta_id AS athleteId, prueba_id AS eventId, pista_id AS trackId,
         DATE_FORMAT(fecha, '%Y-%m-%d') AS date, resultado AS result, categoria AS category
         FROM marcas ORDER BY fecha DESC, id DESC"
    )->fetchAll();
    $users = users($db);
    return compact('athletes', 'events', 'tracks', 'marks', 'users');
}

function publicMarks(PDO $db): array
{
    $events = execute(
        $db,
        "SELECT id, nombre AS name, CASE sentido_resultado WHEN 'mayor' THEN 'higher' ELSE 'lower' END AS resultDirection FROM pruebas ORDER BY nombre"
    )->fetchAll();
    $athletes = execute(
        $db,
        "SELECT DISTINCT a.id, CONCAT(a.nombre, ' ', a.apellidos) AS name
         FROM atletas a JOIN marcas m ON m.atleta_id = a.id ORDER BY a.apellidos, a.nombre"
    )->fetchAll();
    $marks = execute(
        $db,
        "SELECT m.id, m.atleta_id AS athleteId, m.prueba_id AS eventId, CONCAT(a.nombre, ' ', a.apellidos) AS athlete,
         p.nombre AS event, CASE p.sentido_resultado WHEN 'mayor' THEN 'higher' ELSE 'lower' END AS resultDirection,
         m.resultado AS result, m.categoria AS category,
         DATE_FORMAT(m.fecha, '%Y-%m-%d') AS date,
         CONCAT(t.nombre, ' - ', t.localidad) AS track
         FROM marcas m
         JOIN atletas a ON a.id = m.atleta_id
         JOIN pruebas p ON p.id = m.prueba_id
         JOIN pistas t ON t.id = m.pista_id
         ORDER BY m.creado_en DESC, m.id DESC LIMIT 30"
    )->fetchAll();
    $totalMarks = (int) execute($db, 'SELECT COUNT(*) AS total FROM marcas')->fetch()['total'];
    return [
        'events' => $events,
        'athletes' => $athletes,
        'categories' => ['sub8', 'sub10', 'sub12', 'sub14', 'sub16', 'sub18', 'sub20', 'sub23', 'senior', 'master'],
        'marks' => $marks,
        'counts' => ['athletes' => count($athletes), 'events' => count($events), 'marks' => $totalMarks],
    ];
}

function publicRanking(PDO $db, int $eventId, ?string $category): array
{
    $categories = ['sub8', 'sub10', 'sub12', 'sub14', 'sub16', 'sub18', 'sub20', 'sub23', 'senior', 'master'];
    if ($category !== null && !in_array($category, $categories, true)) {
        throw new ApiException('La categoria indicada no existe.');
    }
    $event = execute(
        $db,
        "SELECT id, nombre AS name, CASE sentido_resultado WHEN 'mayor' THEN 'higher' ELSE 'lower' END AS resultDirection
         FROM pruebas WHERE id = ?",
        [$eventId]
    )->fetch();
    if (!$event) {
        throw new ApiException('La prueba indicada no existe.', 404);
    }
    $parameters = [$eventId];
    $categorySql = '';
    if ($category !== null) {
        $categorySql = ' AND m.categoria = ?';
        $parameters[] = $category;
    }
    $marks = execute(
        $db,
        "SELECT m.id, m.atleta_id AS athleteId, m.prueba_id AS eventId, CONCAT(a.nombre, ' ', a.apellidos) AS athlete,
         p.nombre AS event, CASE p.sentido_resultado WHEN 'mayor' THEN 'higher' ELSE 'lower' END AS resultDirection,
         m.resultado AS result, m.categoria AS category, DATE_FORMAT(m.fecha, '%Y-%m-%d') AS date,
         CONCAT(t.nombre, ' - ', t.localidad) AS track
         FROM marcas m
         JOIN atletas a ON a.id = m.atleta_id
         JOIN pruebas p ON p.id = m.prueba_id
         JOIN pistas t ON t.id = m.pista_id
         WHERE m.prueba_id = ?{$categorySql}",
        $parameters
    )->fetchAll();
    $best = [];
    foreach ($marks as $mark) {
        $mark['_value'] = comparableResult($mark['result']);
        $key = (string) $mark['athleteId'];
        $better = !isset($best[$key]) ||
            ($mark['resultDirection'] === 'higher'
                ? $mark['_value'] > $best[$key]['_value']
                : $mark['_value'] < $best[$key]['_value']);
        if ($better) {
            $best[$key] = $mark;
        }
    }
    $marks = array_values($best);
    usort($marks, static function (array $first, array $second): int {
        $result = $first['resultDirection'] === 'higher'
            ? $second['_value'] <=> $first['_value']
            : $first['_value'] <=> $second['_value'];
        return $result !== 0 ? $result : strcasecmp($first['athlete'], $second['athlete']);
    });
    foreach ($marks as &$mark) {
        unset($mark['_value']);
    }
    unset($mark);
    return compact('event', 'category', 'marks');
}

function publicAthleteHistory(PDO $db, int $athleteId): array
{
    $athlete = execute(
        $db,
        "SELECT id, CONCAT(nombre, ' ', apellidos) AS name FROM atletas WHERE id = ?",
        [$athleteId]
    )->fetch();
    if (!$athlete) {
        throw new ApiException('El atleta indicado no existe.', 404);
    }
    $marks = execute(
        $db,
        "SELECT m.id, m.prueba_id AS eventId, p.nombre AS event,
         CASE p.sentido_resultado WHEN 'mayor' THEN 'higher' ELSE 'lower' END AS resultDirection,
         m.resultado AS result, m.categoria AS category, DATE_FORMAT(m.fecha, '%Y-%m-%d') AS date,
         CONCAT(t.nombre, ' - ', t.localidad) AS track
         FROM marcas m
         JOIN pruebas p ON p.id = m.prueba_id
         JOIN pistas t ON t.id = m.pista_id
         WHERE m.atleta_id = ?
         ORDER BY m.categoria, p.nombre, m.fecha DESC, m.id DESC",
        [$athleteId]
    )->fetchAll();
    return compact('athlete', 'marks');
}

function createItem(PDO $db, string $resource, array $payload): void
{
    if ($resource === 'athletes') {
        execute($db, 'INSERT INTO atletas (nombre, apellidos, fecha_nacimiento) VALUES (?, ?, ?)', [
            requiredText($payload, 'name', 'nombre'),
            requiredText($payload, 'surname', 'apellidos'),
            requiredDate($payload, 'birthdate', 'fecha de nacimiento'),
        ]);
    } elseif ($resource === 'events') {
        execute($db, 'INSERT INTO pruebas (nombre, sentido_resultado) VALUES (?, ?)', [
            requiredText($payload, 'name', 'prueba'),
            storedResultDirection(requiredResultDirection($payload)),
        ]);
    } elseif ($resource === 'tracks') {
        execute($db, 'INSERT INTO pistas (nombre, localidad) VALUES (?, ?)', [
            requiredText($payload, 'name', 'pista'),
            requiredText($payload, 'city', 'localidad'),
        ]);
    } else {
        writeMark($db, $payload);
    }
}

function ensureExists(PDO $db, string $table, int $id): void
{
    if (!execute($db, "SELECT id FROM {$table} WHERE id = ?", [$id])->fetch()) {
        throw new ApiException('El registro no existe.', 404);
    }
}

function updateItem(PDO $db, string $resource, int $id, array $payload): void
{
    if ($resource === 'athletes') {
        ensureExists($db, 'atletas', $id);
        $birthdate = requiredDate($payload, 'birthdate', 'fecha de nacimiento');
        $firstDate = execute($db, 'SELECT MIN(fecha) AS first_date FROM marcas WHERE atleta_id = ?', [$id])
            ->fetch()['first_date'];
        if ($firstDate && $firstDate < $birthdate) {
            throw new ApiException('La fecha de nacimiento es posterior a una marca del atleta.');
        }
        execute($db, 'UPDATE atletas SET nombre = ?, apellidos = ?, fecha_nacimiento = ? WHERE id = ?', [
            requiredText($payload, 'name', 'nombre'),
            requiredText($payload, 'surname', 'apellidos'),
            $birthdate,
            $id,
        ]);
        foreach (execute($db, 'SELECT id, fecha FROM marcas WHERE atleta_id = ?', [$id])->fetchAll() as $mark) {
            execute($db, 'UPDATE marcas SET categoria = ? WHERE id = ?', [
                categoryForDates($birthdate, $mark['fecha']),
                $mark['id'],
            ]);
        }
    } elseif ($resource === 'events') {
        ensureExists($db, 'pruebas', $id);
        execute($db, 'UPDATE pruebas SET nombre = ?, sentido_resultado = ? WHERE id = ?', [
            requiredText($payload, 'name', 'prueba'), storedResultDirection(requiredResultDirection($payload)), $id,
        ]);
    } elseif ($resource === 'tracks') {
        ensureExists($db, 'pistas', $id);
        execute($db, 'UPDATE pistas SET nombre = ?, localidad = ? WHERE id = ?', [
            requiredText($payload, 'name', 'pista'),
            requiredText($payload, 'city', 'localidad'),
            $id,
        ]);
    } else {
        writeMark($db, $payload, $id);
    }
}

function writeMark(PDO $db, array $payload, ?int $id = null): void
{
    $athleteId = requiredId($payload, 'athleteId', 'atleta');
    $eventId = requiredId($payload, 'eventId', 'prueba');
    $trackId = requiredId($payload, 'trackId', 'pista');
    $performanceDate = requiredDate($payload, 'date', 'fecha');
    $result = requiredResult($payload);
    $athlete = execute($db, 'SELECT fecha_nacimiento FROM atletas WHERE id = ?', [$athleteId])->fetch();
    if (!$athlete) {
        throw new ApiException('El atleta indicado no existe.');
    }
    ensureExists($db, 'pruebas', $eventId);
    ensureExists($db, 'pistas', $trackId);
    $category = categoryForDates($athlete['fecha_nacimiento'], $performanceDate);
    if ($id === null) {
        execute(
            $db,
            'INSERT INTO marcas (atleta_id, prueba_id, pista_id, fecha, resultado, categoria) VALUES (?, ?, ?, ?, ?, ?)',
            [$athleteId, $eventId, $trackId, $performanceDate, $result, $category]
        );
    } else {
        ensureExists($db, 'marcas', $id);
        execute(
            $db,
            'UPDATE marcas SET atleta_id = ?, prueba_id = ?, pista_id = ?, fecha = ?, resultado = ?, categoria = ? WHERE id = ?',
            [$athleteId, $eventId, $trackId, $performanceDate, $result, $category, $id]
        );
    }
}

function importAthletes(PDO $db, array $payload): array
{
    $rows = $payload['athletes'] ?? null;
    if (!is_array($rows)) {
        throw new ApiException('No se han recibido atletas para importar.');
    }
    $imported = $duplicates = $invalid = 0;
    foreach ($rows as $row) {
        try {
            if (!is_array($row)) {
                throw new ApiException('Fila no valida.');
            }
            execute($db, 'INSERT INTO atletas (nombre, apellidos, fecha_nacimiento) VALUES (?, ?, ?)', [
                requiredText($row, 'name', 'nombre'),
                requiredText($row, 'surname', 'apellidos'),
                requiredDate($row, 'birthdate', 'fecha de nacimiento'),
            ]);
            $imported++;
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $duplicates++;
            } else {
                throw $exception;
            }
        } catch (ApiException) {
            $invalid++;
        }
    }
    return compact('imported', 'duplicates', 'invalid');
}

function route(PDO $db, string $method, string $path, array $payload): array
{
    if ($method === 'GET' && $path === '/auth/status') {
        $total = (int) execute($db, 'SELECT COUNT(*) AS total FROM usuarios')->fetch()['total'];
        return [[
            'setupRequired' => $total === 0,
            'user' => currentUser($db),
        ], 200];
    }
    if ($method === 'POST' && $path === '/auth/setup') {
        $total = (int) execute($db, 'SELECT COUNT(*) AS total FROM usuarios')->fetch()['total'];
        if ($total !== 0) {
            throw new ApiException('La configuracion inicial ya se ha realizado.', 409);
        }
        $_SESSION['user_id'] = createUser($db, $payload, true);
        session_regenerate_id(true);
        return [['user' => currentUser($db)], 201];
    }
    if ($method === 'POST' && $path === '/auth/login') {
        $username = requiredUsername($payload);
        $user = execute($db, 'SELECT id, password_hash, activo FROM usuarios WHERE usuario = ?', [$username])->fetch();
        $password = (string) ($payload['password'] ?? '');
        if (!$user || !(bool) $user['activo'] || !password_verify($password, $user['password_hash'])) {
            throw new ApiException('Usuario o contrasena incorrectos.', 401);
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        return [['user' => currentUser($db)], 200];
    }
    if ($method === 'POST' && $path === '/auth/logout') {
        unset($_SESSION['user_id']);
        session_regenerate_id(true);
        return [['ok' => true], 200];
    }
    if ($method === 'GET' && $path === '/public/marks') {
        return [publicMarks($db), 200];
    }
    if ($method === 'GET' && $path === '/public/ranking') {
        $eventId = filter_var($_GET['eventId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$eventId) {
            throw new ApiException('Selecciona una prueba para consultar el ranking.');
        }
        $category = trim((string) ($_GET['category'] ?? ''));
        return [publicRanking($db, (int) $eventId, $category === '' ? null : $category), 200];
    }
    if ($method === 'GET' && preg_match('#^/public/athletes/(\d+)/history$#', $path, $matches)) {
        return [publicAthleteHistory($db, (int) $matches[1]), 200];
    }
    $actor = requireUser($db);
    if ($method === 'GET' && $path === '/bootstrap') {
        return [bootstrap($db), 200];
    }
    if ($method === 'POST' && $path === '/users') {
        createUser($db, $payload);
        return [['ok' => true], 201];
    }
    if (preg_match('#^/users/(\d+)$#', $path, $matches)) {
        if ($method === 'PUT') {
            updateUser($db, (int) $matches[1], $payload, $actor);
            return [['ok' => true], 200];
        }
        if ($method === 'DELETE') {
            deleteUser($db, (int) $matches[1], $actor);
            return [['ok' => true], 200];
        }
    }
    if ($method === 'POST' && $path === '/athletes/import') {
        return [importAthletes($db, $payload), 200];
    }
    if ($method === 'POST' && $path === '/events/seed') {
        $statement = $db->prepare('INSERT IGNORE INTO pruebas (nombre, sentido_resultado) VALUES (?, ?)');
        foreach (USUAL_EVENTS as $name) {
            $statement->execute([$name, storedResultDirection(usualResultDirection($name))]);
        }
        return [['ok' => true], 201];
    }
    if ($method === 'POST' && preg_match('#^/(athletes|events|tracks|marks)$#', $path, $matches)) {
        createItem($db, $matches[1], $payload);
        return [['ok' => true], 201];
    }
    if (preg_match('#^/(athletes|events|tracks|marks)/(\d+)$#', $path, $matches)) {
        $resource = $matches[1];
        $id = (int) $matches[2];
        if ($method === 'PUT') {
            updateItem($db, $resource, $id, $payload);
            return [['ok' => true], 200];
        }
        if ($method === 'DELETE') {
            $table = ['athletes' => 'atletas', 'events' => 'pruebas', 'tracks' => 'pistas', 'marks' => 'marcas'][$resource];
            ensureExists($db, $table, $id);
            execute($db, "DELETE FROM {$table} WHERE id = ?", [$id]);
            return [['ok' => true], 200];
        }
    }
    throw new ApiException('Ruta no encontrada.', 404);
}

try {
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $payload = in_array($method, ['POST', 'PUT'], true) ? input() : [];
    $db = connection();
    $db->beginTransaction();
    [$response, $status] = route($db, $method, path(), $payload);
    $db->commit();
    respond($response, $status);
} catch (ApiException $exception) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    respond(['error' => $exception->getMessage()], $exception->status);
} catch (PDOException $exception) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    $message = $exception->getCode() === '23000'
        ? 'Ya existe un registro con esos datos o tiene marcas asociadas.'
        : 'Error de base de datos.';
    respond(['error' => $message], $exception->getCode() === '23000' ? 409 : 500);
} catch (RuntimeException $exception) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    respond(['error' => $exception->getMessage()], 500);
} catch (Throwable) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    respond(['error' => 'Error interno del servidor.'], 500);
}
