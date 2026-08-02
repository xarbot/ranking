<?php
declare(strict_types=1);

const RANKING_RESULTS_ENTRY_ROWS = 500;
const RANKING_RESULTS_HEADERS = ['Ámbito / Grupo', 'Prueba', 'Característica técnica', 'Marca', 'Fecha', 'Ciudad', 'Pista'];
const RANKING_RESULTS_MULTI_HEADERS = ['Atleta', 'Ámbito / Grupo', 'Prueba', 'Característica técnica', 'Marca', 'Fecha', 'Ciudad', 'Pista'];

function rankingDefaultEventCatalogue(): array
{
    $trackCatalogue = [
        'Curses' => ['60', '80', '100', '120', '150', '200', '300', '400', '600', '800', '1000', '1500', 'Milla', '2000', '3000', '5000', '10000'],
        'Tanques' => ['60 mt', '80 mt', '100 mt', '110 mt', '220 mt', '300 mt', '400 mt'],
        'Relleus' => ['4x60', '4x80', '4x100', '4x200', '4x300', '4x400', '3x600'],
        'Obstacles' => ['1000 sense ria', '1500', '2000', '3000'],
        'Salts' => ['Llargada', 'Triple', 'Alçada', 'Perxa'],
        'Llançaments' => ['Pes', 'Disc', 'Javelina', 'Martell', 'Martell pesat'],
        'Marxa' => ['1000', '2000', '3000', '5000', '10000'],
    ];

    return [
        'Pista Cubierta' => $trackCatalogue,
        'Aire Libre' => $trackCatalogue,
        'Ruta' => [
            'Curses' => ['Milla', '5km', '10km', 'Mitja marató', 'Marató'],
            'Marxa' => ['1km', '2km', '3km', '5km', '10km', 'Mitja marató', 'Marató'],
        ],
    ];
}

function rankingAreaLabel(string $area): string
{
    return [
        'pista_cubierta' => 'Pista Cubierta',
        'aire_libre' => 'Aire Libre',
        'ruta' => 'Ruta',
    ][$area] ?? $area;
}

function rankingCatalogueFromEvents(array $events): array
{
    $catalogue = [];
    foreach ($events as $event) {
        $area = rankingAreaLabel((string) ($event['area'] ?? $event['ambito'] ?? ''));
        $group = (string) ($event['eventGroup'] ?? $event['grupo'] ?? '');
        $name = (string) ($event['name'] ?? $event['nombre'] ?? '');
        if ($area === '' || $group === '' || $name === '') {
            continue;
        }
        $catalogue[$area][$group][] = $name;
    }
    foreach ($catalogue as &$groups) {
        foreach ($groups as &$names) {
            $names = array_values(array_unique($names));
        }
        unset($names);
    }
    unset($groups);
    return $catalogue;
}

function rankingXml(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function rankingSafeName(string $value): string
{
    $plain = strtr($value, ['ç' => 'c', 'Ç' => 'C', 'à' => 'a', 'á' => 'a', 'è' => 'e', 'é' => 'e', 'í' => 'i', 'ò' => 'o', 'ó' => 'o', 'ú' => 'u']);
    return preg_replace('/[^A-Za-z0-9_]/', '_', str_replace(' ', '_', $plain)) ?? $plain;
}

function rankingColumn(int $index): string
{
    $text = '';
    while ($index > 0) {
        $remainder = ($index - 1) % 26;
        $text = chr(65 + $remainder) . $text;
        $index = intdiv($index - 1, 26);
    }
    return $text;
}

function rankingCell(string $reference, string $value, int $style = 0): string
{
    return '<c r="' . $reference . '" s="' . $style . '" t="inlineStr"><is><t>' . rankingXml($value) . '</t></is></c>';
}

function rankingSheetRow(int $number, array $values, int $style = 0): string
{
    $cells = '';
    foreach ($values as $position => $value) {
        if ($value !== '') {
            $cells .= rankingCell(rankingColumn($position + 1) . $number, (string) $value, $style);
        }
    }
    return '<row r="' . $number . '">' . $cells . '</row>';
}

function rankingWorksheet(
    string $rows,
    string $columns,
    string $validations = '',
    string $lastColumn = 'H',
    int $lastRow = RANKING_RESULTS_ENTRY_ROWS + 1,
    bool $excelStrict = false,
    bool $includeAutoFilter = true,
    ?string $autoFilterRef = null,
    bool $includeSheetProtection = true
): string
{
    $dimension = $excelStrict ? '<dimension ref="A1:' . $lastColumn . $lastRow . '"/>' : '';
    $sheetFormat = $excelStrict ? '<sheetFormatPr defaultRowHeight="15"/>' : '';
    $protection = (!$excelStrict && $includeSheetProtection) ? '<sheetProtection sheet="0"/>' : '';
    $autoFilter = $includeAutoFilter ? '<autoFilter ref="' . ($autoFilterRef ?? 'A1:' . $lastColumn . $lastRow) . '"/>' : '';
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . $dimension . '<sheetViews><sheetView workbookViewId="0"/></sheetViews>' . $sheetFormat
        . '<cols>' . $columns . '</cols><sheetData>' . $rows . '</sheetData>'
        . $protection . $autoFilter
        . $validations . '</worksheet>';
}

function rankingBuildListSheet(array $cities, array $catalogue, bool $excelStrict = false): array
{
    $scopes = [];
    foreach ($catalogue as $area => $groups) {
        foreach ($groups as $group => $_events) {
            $scopes[] = $area . ' / ' . $group;
        }
    }
    $columns = [['Ciudad', ...$cities], ['Ámbito / Grupo', ...$scopes]];
    $ranges = [['Ciudades', "'Listas'!\$A\$2:\$A\$" . (count($cities) + 1)], ['Ambitos_Grupos', "'Listas'!\$B\$2:\$B\$" . (count($scopes) + 1)]];
    foreach ($catalogue as $area => $groups) {
        foreach ($groups as $group => $events) {
            $scope = $area . ' / ' . $group;
            $columns[] = ['Pruebas ' . $scope, ...$events];
            $letter = rankingColumn(count($columns));
            $ranges[] = ['Proves_' . rankingSafeName($scope), "'Listas'!\${$letter}\$2:\${$letter}\$" . (count($events) + 1)];
        }
    }
    $rowCount = max(array_map('count', $columns));
    $rows = '';
    for ($position = 0; $position < $rowCount; $position++) {
        $values = [];
        foreach ($columns as $items) {
            $values[] = $items[$position] ?? '';
        }
        $rows .= rankingSheetRow($position + 1, $values, $position === 0 ? 1 : 0);
    }
    $widths = '';
    foreach ($columns as $position => $_items) {
        $number = $position + 1;
        $widths .= '<col min="' . $number . '" max="' . $number . '" width="32" customWidth="1"/>';
    }
    return [rankingWorksheet($rows, $widths, '', rankingColumn(count($columns)), $rowCount, $excelStrict, !$excelStrict), $ranges];
}

function rankingBuildCitySearchSheet(array $cities, bool $excelStrict = false): string
{
    $help = 'En Resultados puedes escribir una ciudad valida. Si tu Excel no autocompleta la lista, busca aqui con el filtro y copia el valor.';
    $rows = rankingSheetRow(1, ['Ciudad', 'Ayuda'], 1);
    foreach ($cities as $position => $city) {
        $rows .= rankingSheetRow($position + 2, [$city, $position === 0 ? $help : '']);
    }
    return rankingWorksheet(
        $rows,
        '<col min="1" max="1" width="46" customWidth="1"/><col min="2" max="2" width="112" customWidth="1"/>',
        '',
        'B',
        count($cities) + 1,
        $excelStrict,
        true,
        'A1:A' . (count($cities) + 1),
        false
    );
}

function rankingBuildEventSearchSheet(array $catalogue, bool $excelStrict = false): string
{
    $help = 'Filtra esta tabla y copia Ámbito / Grupo y Prueba en Resultados. Fecha admitida: AAAA-MM-DD, DD/MM/AAAA o D/M/AA.';
    $rows = rankingSheetRow(1, ['Ámbito / Grupo', 'Prueba', 'Característica técnica', 'Ayuda'], 1);
    $row = 2;
    foreach ($catalogue as $area => $groups) {
        foreach ($groups as $group => $events) {
            foreach ($events as $event) {
                $rows .= rankingSheetRow($row, [$area . ' / ' . $group, $event, '', $row === 2 ? $help : '']);
                $row++;
            }
        }
    }
    return rankingWorksheet(
        $rows,
        '<col min="1" max="1" width="36" customWidth="1"/><col min="2" max="2" width="26" customWidth="1"/><col min="3" max="3" width="26" customWidth="1"/><col min="4" max="4" width="90" customWidth="1"/>',
        '',
        'D',
        $row - 1,
        $excelStrict,
        !$excelStrict,
        null,
        false
    );
}

function rankingValidation(string $kind, string $reference, string $formula, string $prompt, string $error): string
{
    return '<dataValidation type="list" allowBlank="0" showInputMessage="1" showErrorMessage="1" errorStyle="stop" sqref="' . $reference
        . '" promptTitle="' . rankingXml($kind) . '" prompt="' . rankingXml($prompt) . '" errorTitle="Valor no válido" error="' . rankingXml($error) . '"><formula1>'
        . rankingXml($formula) . '</formula1></dataValidation>';
}

function rankingBuildResultsSheet(bool $includeAthlete = false, bool $excelStrict = false, string $cityFormula = 'Ciudades', iterable $dataRows = []): string
{
    $offset = $includeAthlete ? 1 : 0;
    $scope = rankingColumn(1 + $offset);
    $event = rankingColumn(2 + $offset);
    $city = rankingColumn(6 + $offset);
    $headers = $includeAthlete ? RANKING_RESULTS_MULTI_HEADERS : RANKING_RESULTS_HEADERS;
    $rows = rankingSheetRow(1, $headers, 1);
    $lastDataRow = 1;
    foreach ($dataRows as $dataRow) {
        $lastDataRow++;
        $rows .= rankingSheetRow($lastDataRow, $dataRow);
    }
    $lastRow = max(RANKING_RESULTS_ENTRY_ROWS + 1, $lastDataRow);
    $items = [
        rankingValidation('Ámbito / Grupo', $scope . '2:' . $scope . $lastRow, 'Ambitos_Grupos', 'Escribe o escoge el ámbito y grupo.', 'Escoge un ámbito y grupo de la lista.'),
        rankingValidation('Prueba', $event . '2:' . $event . $lastRow, 'INDIRECT("Proves_"&SUBSTITUTE(SUBSTITUTE(SUBSTITUTE(' . $scope . '2," ","_"),"/","_"),"ç","c"))', 'Tras indicar ámbito y grupo, escribe o escoge la prueba.', 'Escoge una prueba válida para el grupo.'),
        rankingValidation('Ciudad', $city . '2:' . $city . $lastRow, $cityFormula, 'Escribe la ciudad o búscala en la pestaña Ciudades.', 'Escoge una ciudad de la lista.'),
    ];
    $widths = $includeAthlete ? [34, 34, 24, 35, 14, 16, 38, 34] : [34, 24, 35, 14, 16, 38, 34];
    $columns = '';
    foreach ($widths as $position => $width) {
        $number = $position + 1;
        $columns .= '<col min="' . $number . '" max="' . $number . '" width="' . $width . '" customWidth="1"/>';
    }
    return rankingWorksheet($rows, $columns, '<dataValidations count="3">' . implode('', $items) . '</dataValidations>', $includeAthlete ? 'H' : 'G', $lastRow, $excelStrict);
}

function rankingStyles(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts>'
        . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF306334"/><bgColor indexed="64"/></patternFill></fill></fills>'
        . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs>'
        . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
}

function rankingWorkbookFiles(array $cities, array $catalogue, bool $includeAthlete = false, bool $microsoft = false, iterable $dataRows = []): iterable
{
    [$listSheet, $ranges] = rankingBuildListSheet($cities, $catalogue, $microsoft);
    if ($microsoft) {
        foreach ($ranges as &$range) {
            if ($range[0] === 'Ciudades') {
                $range[1] = "'Ciudades'!\$A\$2:\$A\$" . (count($cities) + 1);
            }
        }
        unset($range);
    }
    $defined = '';
    foreach ($ranges as [$name, $reference]) {
        $defined .= '<definedName name="' . rankingXml($name) . '">' . rankingXml($reference) . '</definedName>';
    }
    $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheets><sheet name="Resultados" sheetId="1" r:id="rId1"/><sheet name="Listas" sheetId="2" state="hidden" r:id="rId2"/><sheet name="Ciudades" sheetId="3" r:id="rId3"/><sheet name="Pruebas" sheetId="4" r:id="rId4"/></sheets>'
        . '<definedNames>' . $defined . '</definedNames><calcPr calcId="191029" calcMode="auto"/></workbook>';
    yield '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet3.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet4.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>';
    yield '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>';
    yield 'docProps/core.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:creator>Club Atlètic Castellar</dc:creator><dc:title>Plantilla de resultats</dc:title></cp:coreProperties>';
    yield 'docProps/app.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>Club Atlètic Castellar</Application></Properties>';
    yield 'xl/workbook.xml' => $workbook;
    yield 'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet3.xml"/><Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet4.xml"/><Relationship Id="rId5" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    yield 'xl/worksheets/sheet2.xml' => $listSheet;
    unset($listSheet);
    yield 'xl/worksheets/sheet3.xml' => rankingBuildCitySearchSheet($cities, $microsoft);
    yield 'xl/worksheets/sheet4.xml' => rankingBuildEventSearchSheet($catalogue, $microsoft);
    $cityFormula = 'Ciudades!$A$2:$A$' . (count($cities) + 1);
    yield 'xl/worksheets/sheet1.xml' => rankingBuildResultsSheet($includeAthlete, $microsoft, $microsoft ? $cityFormula : 'Ciudades', $dataRows);
    yield 'xl/styles.xml' => rankingStyles();
}

function rankingWriteZipPart($handle, string $content): void
{
    $length = strlen($content);
    $offset = 0;
    while ($offset < $length) {
        $chunk = substr($content, $offset, min(1048576, $length - $offset));
        $written = fwrite($handle, $chunk);
        if ($written === false || $written === 0) {
            throw new RuntimeException('No se ha podido escribir el archivo ZIP.');
        }
        $offset += $written;
    }
}

function rankingWriteZip(string $target, iterable $files): void
{
    $handle = fopen($target, 'wb');
    if ($handle === false) {
        throw new RuntimeException('No se ha podido guardar ' . $target . '.');
    }
    $central = [];
    try {
        foreach ($files as $name => $content) {
            $name = (string) $name;
            $content = (string) $content;
            $compressed = function_exists('gzdeflate') ? gzdeflate($content) : false;
            $method = $compressed === false ? 0 : 8;
            if ($compressed === false) {
                $compressed = $content;
            }
            $crc = crc32($content);
            $size = strlen($content);
            $compressedSize = strlen($compressed);
            $length = strlen($name);
            $offset = ftell($handle);
            if ($offset === false) {
                throw new RuntimeException('No se ha podido escribir el archivo ZIP.');
            }
            rankingWriteZipPart($handle, pack('VvvvvvVVVvv', 0x04034b50, 20, 0, $method, 0, 0, $crc, $compressedSize, $size, $length, 0));
            rankingWriteZipPart($handle, $name);
            rankingWriteZipPart($handle, $compressed);
            $central[] = [$name, $method, $crc, $compressedSize, $size, $offset];
            unset($content, $compressed);
        }
        $centralStart = ftell($handle);
        if ($centralStart === false) {
            throw new RuntimeException('No se ha podido escribir el archivo ZIP.');
        }
        foreach ($central as [$name, $method, $crc, $compressedSize, $size, $offset]) {
            $length = strlen($name);
            rankingWriteZipPart($handle, pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, $method, 0, 0, $crc, $compressedSize, $size, $length, 0, 0, 0, 0, 0, $offset));
            rankingWriteZipPart($handle, $name);
        }
        $centralEnd = ftell($handle);
        if ($centralEnd === false) {
            throw new RuntimeException('No se ha podido escribir el archivo ZIP.');
        }
        $count = count($central);
        rankingWriteZipPart($handle, pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, $centralEnd - $centralStart, $centralStart, 0));
    } catch (Throwable $exception) {
        fclose($handle);
        if (is_file($target)) {
            unlink($target);
        }
        throw $exception;
    }
    fclose($handle);
}

function rankingReadCitiesCsv(string $cityFile): array
{
    $handle = fopen($cityFile, 'rb');
    if ($handle === false) {
        throw new RuntimeException('No se encuentra database/ciudades_es.csv.');
    }
    fgetcsv($handle, separator: ';');
    $cities = [];
    while (($line = fgetcsv($handle, separator: ';')) !== false) {
        if (isset($line[0]) && trim($line[0]) !== '') {
            $cities[] = trim($line[0]) . (isset($line[1]) && trim($line[1]) !== '' ? ' (' . trim($line[1]) . ')' : '');
        }
    }
    fclose($handle);
    return $cities;
}
