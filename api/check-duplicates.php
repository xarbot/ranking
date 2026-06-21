<?php declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/env.php';

// Validar filtros GET opcionales
$athleteIdFilter = isset($_GET['athlete_id']) ? filter_var($_GET['athlete_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : null;
if (isset($_GET['athlete_id']) && $athleteIdFilter === false) {
    failJson('El filtro athlete_id no es valido.');
}

$pruebaIdFilter = isset($_GET['prueba_id']) ? filter_var($_GET['prueba_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : null;
if (isset($_GET['prueba_id']) && $pruebaIdFilter === false) {
    failJson('El filtro prueba_id no es valido.');
}

function respondJson(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

function failJson(string $message, int $status = 500): never
{
    respondJson(['success' => false, 'error' => $message], $status);
}

/**
 * Detecta grupos de marcas duplicadas en la tabla marcas.
 *
 * Un grupo duplicado es un conjunto de 2 o mas registros que coinciden en
 * todos los campos relevantes para identificar una marca:
 * atleta_id, prueba_id, fecha, resultado, ciudad_id, nombre_pista,
 * caracteristica_tecnica, categoria y pista_id.
 *
 * Los campos que pueden ser NULL se comparan con el operador null-safe `<=>`
 * de MySQL, de forma que NULL <=> NULL se considera coincidencia y
 * NULL <=> valor no coincide.
 *
 * @param array|null $athleteIdFilter Filtro opcional por atleta_id
 * @param array|null $pruebaIdFilter Filtro opcional por prueba_id
 */
function detectDuplicateGroups(PDO $db, ?int $athleteIdFilter = null, ?int $pruebaIdFilter = null): array
{
    $sql = 'SELECT
    m.id,
    m.atleta_id,
    m.prueba_id,
    m.fecha,
    m.resultado,
    m.ciudad_id,
    m.nombre_pista,
    m.caracteristica_tecnica,
    m.categoria,
    m.pista_id,
    a.nombre      AS atleta_nombre,
    a.apellidos    AS atleta_apellidos,
    p.nombre      AS prueba_nombre,
    p.ambito      AS prueba_ambito,
    p.grupo       AS prueba_grupo,
    c.nombre      AS ciudad_nombre,
    c.provincia   AS ciudad_provincia
FROM marcas m
LEFT JOIN atletas a ON a.id = m.atleta_id
LEFT JOIN pruebas p ON p.id = m.prueba_id
LEFT JOIN ciudades c ON c.id = m.ciudad_id'
        . ($athleteIdFilter !== null ? ' WHERE m.atleta_id = ?' : '')
        . ($pruebaIdFilter !== null ? ($athleteIdFilter !== null ? ' AND m.prueba_id = ?' : ' WHERE m.prueba_id = ?') : '')
        . ' AND EXISTS (
    SELECT 1
    FROM marcas m2
    WHERE m2.id <> m.id'
        . ($athleteIdFilter !== null ? ' AND m2.atleta_id = m.atleta_id' : '')
        . ($pruebaIdFilter !== null ? ' AND m2.prueba_id = m.prueba_id' : '')
        . ' AND (m2.atleta_id            <=> m.atleta_id)
      AND (m2.prueba_id             <=> m.prueba_id)
      AND (m2.fecha                 <=> m.fecha)
      AND (m2.resultado             <=> m.resultado)
      AND (m2.ciudad_id             <=> m.ciudad_id)
      AND (m2.nombre_pista          <=> m.nombre_pista)
      AND (m2.caracteristica_tecnica <=> m.caracteristica_tecnica)
      AND (m2.categoria             <=> m.categoria)
      AND (m2.pista_id              <=> m.pista_id)
)';

    $params = [];
    if ($athleteIdFilter !== null) {
        $params[] = $athleteIdFilter;
    }
    if ($pruebaIdFilter !== null) {
        $params[] = $pruebaIdFilter;
    }

    $statement = $db->prepare($sql);
    $statement->execute($params);
    $rows = $statement->fetchAll();

    $groups = [];
    foreach ($rows as $row) {
        $key = json_encode([
            'atleta_id'             => $row['atleta_id'],
            'prueba_id'             => $row['prueba_id'],
            'fecha'                 => $row['fecha'],
            'resultado'             => $row['resultado'],
            'ciudad_id'             => $row['ciudad_id'],
            'nombre_pista'          => $row['nombre_pista'],
            'caracteristica_tecnica' => $row['caracteristica_tecnica'],
            'categoria'             => $row['categoria'],
            'pista_id'              => $row['pista_id'],
        ], JSON_UNESCAPED_UNICODE);

        if (!isset($groups[$key])) {
            $groups[$key] = [
                'count' => 0,
                'ids'   => [],
                'atleta_id'              => $row['atleta_id'] !== null ? (int) $row['atleta_id'] : null,
                'prueba_id'              => $row['prueba_id'] !== null ? (int) $row['prueba_id'] : null,
                'fecha'                  => $row['fecha'],
                'resultado'              => $row['resultado'],
                'ciudad_id'              => $row['ciudad_id'] !== null ? (int) $row['ciudad_id'] : null,
                'nombre_pista'           => $row['nombre_pista'],
                'caracteristica_tecnica'  => $row['caracteristica_tecnica'],
                'categoria'              => $row['categoria'],
                'pista_id'               => $row['pista_id'] !== null ? (int) $row['pista_id'] : null,
                'atleta'                 => [
                    'nombre'   => $row['atleta_nombre'],
                    'apellidos' => $row['atleta_apellidos'],
                ],
                'prueba'                 => [
                    'nombre' => $row['prueba_nombre'],
                    'ambito' => $row['prueba_ambito'],
                    'grupo'  => $row['prueba_grupo'],
                ],
                'ciudad'                 => [
                    'nombre'    => $row['ciudad_nombre'],
                    'provincia' => $row['ciudad_provincia'],
                ],
            ];
        }

        $groups[$key]['count']++;
        $groups[$key]['ids'][] = (int) $row['id'];
    }

    $duplicateGroups = array_values($groups);
    $totalRecords = 0;
    foreach ($duplicateGroups as $group) {
        $totalRecords += $group['count'];
    }

    return [
        'success'         => true,
        'total_groups'    => count($duplicateGroups),
        'total_records'   => $totalRecords,
        'duplicate_groups' => $duplicateGroups,
    ];
}

try {
    $db = databaseConnection(dirname(__DIR__, 2) . '/.env');
    $payload = detectDuplicateGroups($db, $athleteIdFilter, $pruebaIdFilter);
    respondJson($payload, 200);
} catch (Throwable $e) {
    failJson('Error interno del servidor.', 500);
}
