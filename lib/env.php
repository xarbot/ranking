<?php
declare(strict_types=1);

function loadEnvironment(string $file): void
{
    if (!is_readable($file)) {
        throw new RuntimeException('Falta configurar el archivo .env en el servidor.');
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        throw new RuntimeException('No se ha podido leer el archivo .env.');
    }

    foreach ($lines as $lineNumber => $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!preg_match('/^([A-Z][A-Z0-9_]*)\s*=\s*(.*)$/', $line, $matches)) {
            throw new RuntimeException(sprintf('La linea %d del archivo .env no es valida.', $lineNumber + 1));
        }

        $name = $matches[1];
        $value = trim($matches[2]);
        if (strlen($value) >= 2 && (
            ($value[0] === '"' && str_ends_with($value, '"')) ||
            ($value[0] === "'" && str_ends_with($value, "'"))
        )) {
            $value = substr($value, 1, -1);
        }
        putenv($name . '=' . $value);
    }
}

function databaseConfiguration(string $envFile): array
{
    loadEnvironment($envFile);
    $values = [];
    foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $key) {
        $value = getenv($key);
        if ($value === false || trim($value) === '') {
            throw new RuntimeException("La variable {$key} es obligatoria en .env.");
        }
        $values[$key] = $value;
    }

    $port = getenv('DB_PORT');
    $port = $port === false || $port === '' ? '3306' : $port;
    if (!ctype_digit($port) || (int) $port < 1 || (int) $port > 65535) {
        throw new RuntimeException('La variable DB_PORT no contiene un puerto valido.');
    }
    $values['DB_PORT'] = (int) $port;

    return $values;
}

function databaseConnection(string $envFile): PDO
{
    $config = databaseConfiguration($envFile);
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $config['DB_HOST'], $config['DB_PORT'], $config['DB_NAME']);
    return new PDO($dsn, $config['DB_USER'], $config['DB_PASS'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
