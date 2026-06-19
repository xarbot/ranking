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
    $dsn = sprintf('mysql:host=%s;dbname=%s;port=%d;charset=utf8mb4', $host, $dbname, (int)$port);
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException|Exception $e) {
    die("Error en la conexión a la base de datos: " . $e->getMessage());
}

// Consulta para obtener duplicados con los detalles solicitados
// Un duplicado se considera cuando hay dos filas iguales dentro de la tabla marcas
// Es decir, dos registros del mismo atleta, misma prueba, ciudad, fecha,
// con la misma categoría, característica técnica Y el mismo resultado.
// Dos marcas son duplicadas solo si coinciden en TODOS estos campos incluido resultado.
$sql = 'SELECT
    m.atleta_id,
    m.prueba_id,
    m.ciudad_id,
    m.fecha,
    m.caracteristica_tecnica,
    m.categoria,
    atleta.nombre AS nombre_atleta,
    prueba.nombre AS prueba,
    ciudad.nombre AS ciudad,
    m.resultado,
    m.nombre_pista,
    m.pista_id
FROM marcas m
INNER JOIN atletas atleta ON m.atleta_id = atleta.id
INNER JOIN pruebas prueba ON m.prueba_id = prueba.id
INNER JOIN ciudades ciudad ON m.ciudad_id = ciudad.id
WHERE m.id IN (
    SELECT m2.id
    FROM marcas m2
    WHERE m2.atleta_id = m.atleta_id
    AND m2.prueba_id = m.prueba_id
    AND m2.ciudad_id = m.ciudad_id
    AND m2.fecha = m.fecha
    AND (m2.resultado <=> m.resultado)
    AND m2.caracteristica_tecnica = m.caracteristica_tecnica
    AND m2.categoria = m.categoria
    GROUP BY m2.atleta_id, m2.prueba_id, m2.ciudad_id, m2.fecha, m2.caracteristica_tecnica, m2.categoria, m2.resultado
    HAVING COUNT(*) > 1
)';

$duplicates = [];

try {
    $stmt = $pdo->query($sql);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $duplicates[] = [
            'atleta_id' => $row['atleta_id'],
            'prueba_id' => $row['prueba_id'],
            'ciudad_id' => $row['ciudad_id'],
            'fecha' => $row['fecha'],
            'caracteristica_tecnica' => $row['caracteristica_tecnica'],
            'categoria' => $row['categoria'],
            'nombre_atleta' => $row['nombre_atleta'],
            'prueba' => $row['prueba'],
            'ciudad' => $row['ciudad'],
            'resultado' => $row['resultado'],
            'nombre_pista' => $row['nombre_pista'],
            'pista_id' => $row['pista_id']
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
