<?php

// api/check-duplicates.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Simulación de consulta a la base de datos
$duplicates = [
    [
        'atleta_id' => 123,
        'prueba_id' => 456,
        'ciudad_id' => 789,
        'fecha' => '2026-06-01',
        'resultado' => '10.50s',
        'duplicados' => 2
    ],
    [
        'atleta_id' => 789,
        'prueba_id' => 321,
        'ciudad_id' => 654,
        'fecha' => '2026-05-15',
        'resultado' => '2.50m',
        'duplicados' => 3
    ]
];

// Devolver resultados en formato JSON
echo json_encode([
    'status' => 'success',
    'data' => $duplicates
], JSON_PRETTY_PRINT);

exit;
