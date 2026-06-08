<?php

// api/check-duplicates.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../lib/env.php';
loadEnvironment(__DIR__ . '/../../.env'); // Cargar variables del archivo .env (ruta corregida)

try {
    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: 3306;
    $dbname = getenv('DB_NAME');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASS');
    
    if (empty($dbname)) {
        throw new Exception("La variable de entorno DB_NAME no está configurada.");
    }
    
    // Incluir dbname en el DSN para seleccionar la base de datos explícitamente y evitar "No database selected"
    // Incluir dbname en el DSN para seleccionar la base de datos explícitamente y evitar "No database selected"
    $dsn = sprintf('mysql:host=%s;dbname=%s;port=%d;charset=utf8mb4', $host, $dbname, (int)$port);
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException|Exception $e) {
    die("Error en la conexión a la base de datos: " . $e->getMessage());
}

// Consulta para obtener duplicados con los detalles solicitados
$sql = 'SELECT atleta.nombre AS nombre_atleta, prueba.descripcion AS prueba, valor_tecnico, marca_registro, fecha, ciudad.nombre AS ciudad, pista.nombre AS pista FROM marcas INNER JOIN atletas atleta ON marcas.atleta_id = atleta.id INNER JOIN pruebas prueba ON marcas.prueba_id = prueba.id INNER JOIN ciudades ciudad ON marcas.ciudad_id = ciudad.id INNER JOIN pistas pista ON marcas.pista_id = pista.id WHERE duplicados > 1';

$duplicates = [];

try {
    $stmt = $pdo->query($sql);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $duplicates[] = [
            'nombre_atleta' => $row['nombre_atleta'],
            'prueba' => $row['prueba'],
            'valor_tecnico' => $row['valor_tecnico'],
            'marca_registro' => $row['marca_registro'],
            'fecha' => $row['fecha'],
            'ciudad' => $row['ciudad'],
            'pista' => $row['pista']
        ];
    }
} catch (PDOException $e) {
    die("Error en la consulta: " . $e->getMessage());
}

// Devolver resultados en formato JSON
echo json_encode([
    'status' => 'success',
    'data' => $duplicates
], JSON_PRETTY_PRINT);

exit;
