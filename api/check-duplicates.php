<?php

// api/check-duplicates.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../lib/env.php'; // Incluir archivo de configuración de la base de datos

try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASSWORD);
} catch (PDOException $e) {
    die("Error en la conexión a la base de datos: " . $e->getMessage());
}

// Consulta para obtener duplicados con los detalles solicitados
$sql = 'SELECT atleta.nombre AS nombre_atleta, prueba.descripcion AS prueba, valor_tecnico, marca_registro, fecha, ciudad.nombre AS ciudad, pista.nombre AS pista FROM resultados INNER JOIN atletas atleta ON resultados.atleta_id = atleta.id INNER JOIN pruebas prueba ON resultados.prueba_id = prueba.id INNER JOIN ciudades ciudad ON resultados.ciudad_id = ciudad.id INNER JOIN pistas pista ON resultados.pista_id = pista.id WHERE duplicados > 1';

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
