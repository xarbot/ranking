<?php
declare(strict_types=1);

/** Generates the distributable results workbook with catalogue-backed dropdowns. */

const ENTRY_ROWS = 500;
const HEADERS = ['Ámbito / Grupo', 'Prueba', 'Característica técnica', 'Marca', 'Fecha', 'Ciudad', 'Pista'];

$root = dirname(__DIR__);
$cityFile = $root . '/database/ciudades_es.csv';
$output = $root . '/assets/plantilla-resultados.xlsx';
$multiOutput = $root . '/assets/plantilla-resultados-atletas.xlsx';
$microsoftOutput = $root . '/assets/plantilla-resultados-microsoft.xlsx';
$microsoftMultiOutput = $root . '/assets/plantilla-resultados-atletas-microsoft.xlsx';
$trackCatalogue = [
    'Curses' => ['60', '80', '100', '120', '150', '200', '300', '400', '600', '800', '1000', '1500', 'Milla', '2000', '3000', '5000', '10000'],
    'Tanques' => ['60 mt', '80 mt', '100 mt', '110 mt', '220 mt', '300 mt', '400 mt'],
    'Relleus' => ['4x60', '4x80', '4x100', '4x200', '4x300', '4x400', '3x600'],
    'Obstacles' => ['1000 sense ria', '1500', '2000', '3000'],
    'Salts' => ['Llargada', 'Triple', 'Alçada', 'Perxa'],
    'Llançaments' => ['Pes', 'Disc', 'Javelina', 'Martell', 'Martell pesat'],
    'Marxa' => ['1000', '2000', '3000', '5000', '10000'],
];
$catalogue = [
    'Pista Cubierta' => $trackCatalogue,
    'Aire Libre' => $trackCatalogue,
    'Ruta' => [
        'Curses' => ['Milla', '5km', '10km', 'Mitja marató', 'Marató'],
        'Marxa' => ['1km', '2km', '3km', '5km', '10km', 'Mitja marató', 'Marató'],
    ],
];

function xml(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function safeName(string $value): string
{
    $plain = strtr($value, ['ç' => 'c', 'Ç' => 'C', 'à' => 'a', 'á' => 'a', 'è' => 'e', 'é' => 'e', 'í' => 'i', 'ò' => 'o', 'ó' => 'o', 'ú' => 'u']);
    return preg_replace('/[^A-Za-z0-9_]/', '_', str_replace(' ', '_', $plain)) ?? $plain;
}

function column(int $index): string
{
    $text = '';
    while ($index > 0) {
        $remainder = ($index - 1) % 26;
        $text = chr(65 + $remainder) . $text;
        $index = intdiv($index - 1, 26);
    }
    return $text;
}

function cell(string $reference, string $value, int $style = 0): string
{
    return '<c r="' . $reference . '" s="' . $style . '" t="inlineStr"><is><t>' . xml($value) . '</t></is></c>';
}

function sheetRow(int $number, array $values, int $style = 0): string
{
    $cells = '';
    foreach ($values as $position => $value) {
        if ($value !== '') {
            $cells .= cell(column($position + 1) . $number, $value, $style);
        }
    }
    return '<row r="' . $number . '">' . $cells . '</row>';
}

function hiddenSeedRow(int $number, string $reference, string $value): string
{
    return '<row r="' . $number . '" hidden="1" ht="0" customHeight="1">' . cell($reference, $value) . '</row>';
}

function worksheet(string $rows, string $columns, string $validations = '', string $lastColumn = 'H', bool $excelStrict = false): string
{
    $protection = $excelStrict ? '' : '<sheetProtection sheet="0"/>';
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
        . '<cols>' . $columns . '</cols><sheetData>' . $rows . '</sheetData>'
        . $protection . '<autoFilter ref="A1:' . $lastColumn . '501"/>'
        . $validations . '</worksheet>';
}

function columnIndex(string $letters): int
{
    $index = 0;
    foreach (str_split($letters) as $letter) {
        $index = ($index * 26) + (ord($letter) - 64);
    }
    return $index;
}

function sheetDimension(string $sheet): string
{
    preg_match_all('/<c r="([A-Z]+)(\d+)"/', $sheet, $matches, PREG_SET_ORDER);
    $maxColumn = 1;
    $maxRow = 1;
    foreach ($matches as $match) {
        $maxColumn = max($maxColumn, columnIndex($match[1]));
        $maxRow = max($maxRow, (int) $match[2]);
    }
    return 'A1:' . column($maxColumn) . $maxRow;
}

function excelStrictSheet(string $sheet, bool $removeAutoFilter = false): string
{
    $sheet = preg_replace('/<sheetProtection[^>]*\/>/', '', $sheet) ?? $sheet;
    if ($removeAutoFilter) {
        $sheet = preg_replace('/<autoFilter[^>]*\/>/', '', $sheet) ?? $sheet;
    }
    if (!str_contains($sheet, '<dimension ')) {
        $dimension = '<dimension ref="' . sheetDimension($sheet) . '"/>';
        $sheet = preg_replace('/(<worksheet\b[^>]*>)/', '$1' . $dimension, $sheet, 1) ?? $sheet;
    }
    if (!str_contains($sheet, '<sheetFormatPr ')) {
        $sheet = str_replace('</sheetViews>', '</sheetViews><sheetFormatPr defaultRowHeight="15"/>', $sheet);
    }
    return $sheet;
}

function readCities(string $cityFile): array
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

function buildListSheet(array $cities, array $catalogue): array
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
            $letter = column(count($columns));
            $ranges[] = ['Proves_' . safeName($scope), "'Listas'!\${$letter}\$2:\${$letter}\$" . (count($events) + 1)];
        }
    }
    $rowCount = max(array_map('count', $columns));
    $rows = '';
    for ($position = 0; $position < $rowCount; $position++) {
        $values = [];
        foreach ($columns as $items) {
            $values[] = $items[$position] ?? '';
        }
        $rows .= sheetRow($position + 1, $values, $position === 0 ? 1 : 0);
    }
    $widths = '';
    foreach ($columns as $position => $_items) {
        $number = $position + 1;
        $widths .= '<col min="' . $number . '" max="' . $number . '" width="32" customWidth="1"/>';
    }
    return [worksheet($rows, $widths), $ranges];
}

function buildCitySearchSheet(array $cities): string
{
    $help = 'En Resultados puedes escribir una ciudad valida. Si tu Excel no autocompleta la lista, busca aqui con el filtro y copia el valor.';
    $rows = sheetRow(1, ['Ciudad', 'Ayuda'], 1);
    foreach ($cities as $position => $city) {
        $rows .= sheetRow($position + 2, [$city, $position === 0 ? $help : '']);
    }
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
        . '<cols><col min="1" max="1" width="46" customWidth="1"/><col min="2" max="2" width="112" customWidth="1"/></cols>'
        . '<sheetData>' . $rows . '</sheetData>'
        . '<autoFilter ref="A1:A' . (count($cities) + 1) . '"/></worksheet>';
}

function buildEventSearchSheet(array $catalogue): string
{
    $help = 'Filtra esta tabla y copia Ámbito / Grupo y Prueba en Resultados. Fecha admitida: AAAA-MM-DD, DD/MM/AAAA o D/M/AA.';
    $rows = sheetRow(1, ['Ámbito / Grupo', 'Prueba', 'Característica técnica', 'Ayuda'], 1);
    $row = 2;
    foreach ($catalogue as $area => $groups) {
        foreach ($groups as $group => $events) {
            foreach ($events as $event) {
                $technical = in_array($group, ['Tanques', 'Llançaments'], true) ? 'Sí' : 'No';
                $rows .= sheetRow($row, [$area . ' / ' . $group, $event, $technical, $row === 2 ? $help : '']);
                $row++;
            }
        }
    }
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
        . '<cols><col min="1" max="1" width="36" customWidth="1"/><col min="2" max="2" width="26" customWidth="1"/><col min="3" max="3" width="26" customWidth="1"/><col min="4" max="4" width="90" customWidth="1"/></cols>'
        . '<sheetData>' . $rows . '</sheetData>'
        . '<autoFilter ref="A1:D' . ($row - 1) . '"/></worksheet>';
}

function validation(string $kind, string $reference, string $formula, string $prompt, string $error): string
{
    return '<dataValidation type="list" allowBlank="0" showInputMessage="1" showErrorMessage="1" errorStyle="stop" sqref="' . $reference
        . '" promptTitle="' . xml($kind) . '" prompt="' . xml($prompt) . '" errorTitle="Valor no válido" error="' . xml($error) . '"><formula1>'
        . xml($formula) . '</formula1></dataValidation>';
}

function buildResultsSheet(bool $includeAthlete = false, bool $excelStrict = false, string $cityFormula = 'Ciudades', array $citySeeds = []): string
{
    $offset = $includeAthlete ? 1 : 0;
    $scope = column(1 + $offset);
    $event = column(2 + $offset);
    $city = column(6 + $offset);
    $items = [
        validation('Ámbito / Grupo', $scope . '2:' . $scope . '501', 'Ambitos_Grupos', 'Escribe o escoge el ámbito y grupo.', 'Escoge un ámbito y grupo de la lista.'),
        validation('Prueba', $event . '2:' . $event . '501', 'INDIRECT("Proves_"&SUBSTITUTE(SUBSTITUTE(SUBSTITUTE(' . $scope . '2," ","_"),"/","_"),"ç","c"))', 'Tras indicar ámbito y grupo, escribe o escoge la prueba.', 'Escoge una prueba válida para el grupo.'),
        validation('Ciudad', $city . '2:' . $city . '501', $cityFormula, 'Escribe la ciudad o búscala en la pestaña Ciudades.', 'Escoge una ciudad de la lista.'),
    ];
    $headers = $includeAthlete ? ['Atleta', ...HEADERS] : HEADERS;
    $widths = $includeAthlete ? [34, 34, 24, 35, 14, 16, 38, 34] : [34, 24, 35, 14, 16, 38, 34];
    $columns = '';
    foreach ($widths as $position => $width) {
        $number = $position + 1;
        $columns .= '<col min="' . $number . '" max="' . $number . '" width="' . $width . '" customWidth="1"/>';
    }
    $rows = sheetRow(1, $headers, 1);
    foreach ($citySeeds as $position => $seed) {
        $row = 502 + $position;
        $rows .= hiddenSeedRow($row, $city . $row, $seed);
    }
    return worksheet($rows, $columns, '<dataValidations count="3">' . implode('', $items) . '</dataValidations>', $includeAthlete ? 'H' : 'G', $excelStrict);
}

function styles(): string
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

/** Writes an OpenXML package without requiring the optional PHP Zip extension. */
function writeZip(string $target, array $files): void
{
    $body = '';
    $directory = '';
    $offset = 0;
    foreach ($files as $name => $content) {
        $compressed = function_exists('gzdeflate') ? gzdeflate($content) : $content;
        if ($compressed === false) {
            $compressed = $content;
        }
        $method = $compressed === $content ? 0 : 8;
        $crc = crc32($content);
        $size = strlen($content);
        $compressedSize = strlen($compressed);
        $length = strlen($name);
        $local = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, $method, 0, 0, $crc, $compressedSize, $size, $length, 0) . $name . $compressed;
        $directory .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, $method, 0, 0, $crc, $compressedSize, $size, $length, 0, 0, 0, 0, 0, $offset) . $name;
        $body .= $local;
        $offset += strlen($local);
    }
    $count = count($files);
    $contents = $body . $directory . pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, strlen($directory), strlen($body), 0);
    if (file_put_contents($target, $contents) === false) {
        throw new RuntimeException('No se ha podido guardar ' . $target . '.');
    }
}

try {
    $cities = readCities($cityFile);
    [$listSheet, $ranges] = buildListSheet($cities, $catalogue);
    $citySearchSheet = buildCitySearchSheet($cities);
    $eventSearchSheet = buildEventSearchSheet($catalogue);
    $defined = '';
    foreach ($ranges as [$name, $reference]) {
        $defined .= '<definedName name="' . xml($name) . '">' . xml($reference) . '</definedName>';
    }
    $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheets><sheet name="Resultados" sheetId="1" r:id="rId1"/><sheet name="Listas" sheetId="2" state="hidden" r:id="rId2"/><sheet name="Ciudades" sheetId="3" r:id="rId3"/><sheet name="Pruebas" sheetId="4" r:id="rId4"/></sheets>'
        . '<definedNames>' . $defined . '</definedNames><calcPr calcId="191029" calcMode="auto"/></workbook>';
    $files = [
        '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet3.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet4.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>',
        '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>',
        'docProps/core.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:creator>Club Atlètic Castellar</dc:creator><dc:title>Plantilla de resultats</dc:title></cp:coreProperties>',
        'docProps/app.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>Club Atlètic Castellar</Application></Properties>',
        'xl/workbook.xml' => $workbook,
        'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet3.xml"/><Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet4.xml"/><Relationship Id="rId5" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
        'xl/worksheets/sheet1.xml' => buildResultsSheet(),
        'xl/worksheets/sheet2.xml' => $listSheet,
        'xl/worksheets/sheet3.xml' => $citySearchSheet,
        'xl/worksheets/sheet4.xml' => $eventSearchSheet,
        'xl/styles.xml' => styles(),
    ];
    writeZip($output, $files);
    $files['xl/worksheets/sheet1.xml'] = buildResultsSheet(true);
    writeZip($multiOutput, $files);

    $microsoftFiles = $files;
    $microsoftFiles['xl/workbook.xml'] = str_replace(
        "'Listas'!\$A\$2:\$A\$" . (count($cities) + 1),
        "'Ciudades'!\$A\$2:\$A\$" . (count($cities) + 1),
        $workbook
    );
    $microsoftCityFormula = 'Ciudades!$A$2:$A$' . (count($cities) + 1);
    $microsoftFiles['xl/worksheets/sheet1.xml'] = excelStrictSheet(buildResultsSheet(false, true, $microsoftCityFormula, $cities));
    $microsoftFiles['xl/worksheets/sheet2.xml'] = excelStrictSheet($listSheet, true);
    $microsoftFiles['xl/worksheets/sheet3.xml'] = excelStrictSheet($citySearchSheet);
    $microsoftFiles['xl/worksheets/sheet4.xml'] = excelStrictSheet($eventSearchSheet, true);
    writeZip($microsoftOutput, $microsoftFiles);
    $microsoftFiles['xl/worksheets/sheet1.xml'] = excelStrictSheet(buildResultsSheet(true, true, $microsoftCityFormula, $cities));
    writeZip($microsoftMultiOutput, $microsoftFiles);
    echo sprintf("Generades %s, %s, %s i %s amb %d ciutats i %d files de resultats.\n", $output, $multiOutput, $microsoftOutput, $microsoftMultiOutput, count($cities), ENTRY_ROWS);
} catch (Throwable $exception) {
    fwrite(STDERR, 'No se ha podido generar la plantilla: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
