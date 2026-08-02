<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/results_workbook.php';

try {
    $root = dirname(__DIR__);
    $cities = rankingReadCitiesCsv($root . '/database/ciudades_es.csv');
    $catalogue = rankingDefaultEventCatalogue();
    rankingWriteZip($root . '/assets/plantilla-resultados.xlsx', rankingWorkbookFiles($cities, $catalogue));
    rankingWriteZip($root . '/assets/plantilla-resultados-atletas.xlsx', rankingWorkbookFiles($cities, $catalogue, true));
    rankingWriteZip($root . '/assets/plantilla-resultados-microsoft.xlsx', rankingWorkbookFiles($cities, $catalogue, false, true));
    rankingWriteZip($root . '/assets/plantilla-resultados-atletas-microsoft.xlsx', rankingWorkbookFiles($cities, $catalogue, true, true));
    echo sprintf(
        "Generades %s, %s, %s i %s amb %d ciutats i %d files de resultats.\n",
        $root . '/assets/plantilla-resultados.xlsx',
        $root . '/assets/plantilla-resultados-atletas.xlsx',
        $root . '/assets/plantilla-resultados-microsoft.xlsx',
        $root . '/assets/plantilla-resultados-atletas-microsoft.xlsx',
        count($cities),
        RANKING_RESULTS_ENTRY_ROWS
    );
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'No se ha podido generar la plantilla: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
