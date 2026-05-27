<?php
declare(strict_types=1);

/** Generates the distributable results workbook with catalogue-backed dropdowns. */

const ENTRY_ROWS = 500;
const HEADERS = ['Ámbito', 'Grupo', 'Prueba', 'Característica técnica', 'Marca', 'Fecha', 'Ciudad', 'Pista'];

$root = dirname(__DIR__);
$cityFile = $root . '/database/ciudades_es.csv';
$output = $root . '/assets/plantilla-resultados.xlsx';
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

function worksheet(string $rows, string $columns, string $validations = ''): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
        . '<cols>' . $columns . '</cols><sheetData>' . $rows . '</sheetData>'
        . '<autoFilter ref="A1:H501"/><sheetProtection sheet="0"/>'
        . $validations . '</worksheet>';
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
    $columns = [['Ciudad', ...$cities], ['Ámbito', ...array_keys($catalogue)]];
    $ranges = [['Ciudades', "'Listas'!\$A\$2:\$A\$" . (count($cities) + 1)], ['Ambitos', "'Listas'!\$B\$2:\$B\$4"]];
    foreach ($catalogue as $area => $groups) {
        $columns[] = ['Grupos ' . $area, ...array_keys($groups)];
        $letter = column(count($columns));
        $ranges[] = ['Grupos_' . safeName($area), "'Listas'!\${$letter}\$2:\${$letter}\$" . (count($groups) + 1)];
        foreach ($groups as $group => $events) {
            $columns[] = ['Pruebas ' . $area . ' ' . $group, ...$events];
            $letter = column(count($columns));
            $ranges[] = ['Proves_' . safeName($area) . '_' . safeName($group), "'Listas'!\${$letter}\$2:\${$letter}\$" . (count($events) + 1)];
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

function validation(string $kind, string $reference, string $formula, string $prompt, string $error): string
{
    return '<dataValidation type="list" allowBlank="0" showInputMessage="1" showErrorMessage="1" errorStyle="stop" sqref="' . $reference
        . '" promptTitle="' . xml($kind) . '" prompt="' . xml($prompt) . '" errorTitle="Valor no válido" error="' . xml($error) . '"><formula1>'
        . xml($formula) . '</formula1></dataValidation>';
}

function buildResultsSheet(): string
{
    $items = [
        validation('Ámbito', 'A2:A501', 'Ambitos', 'Escoge el ámbito.', 'Escoge un ámbito de la lista.'),
        validation('Grupo', 'B2:B501', 'INDIRECT("Grupos_"&SUBSTITUTE(A2," ","_"))', 'Primero escoge el ámbito.', 'Escoge un grupo válido para el ámbito.'),
        validation('Prueba', 'C2:C501', 'INDIRECT("Proves_"&SUBSTITUTE(A2," ","_")&"_"&SUBSTITUTE(B2,"ç","c"))', 'Primero escoge ámbito y grupo.', 'Escoge una prueba válida para el grupo.'),
        validation('Ciudad', 'G2:G501', 'Ciudades', 'Escribe para buscar y escoge la ciudad.', 'Escoge una ciudad de la lista.'),
    ];
    $widths = [20, 18, 24, 35, 14, 16, 38, 34];
    $columns = '';
    foreach ($widths as $position => $width) {
        $number = $position + 1;
        $columns .= '<col min="' . $number . '" max="' . $number . '" width="' . $width . '" customWidth="1"/>';
    }
    return worksheet(sheetRow(1, HEADERS, 1), $columns, '<dataValidations count="4">' . implode('', $items) . '</dataValidations>');
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
    $defined = '';
    foreach ($ranges as [$name, $reference]) {
        $defined .= '<definedName name="' . xml($name) . '">' . xml($reference) . '</definedName>';
    }
    $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheets><sheet name="Resultados" sheetId="1" r:id="rId1"/><sheet name="Listas" sheetId="2" state="hidden" r:id="rId2"/></sheets>'
        . '<definedNames>' . $defined . '</definedNames><calcPr calcId="191029" calcMode="auto"/></workbook>';
    $files = [
        '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>',
        '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>',
        'docProps/core.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:creator>Club Atlètic Castellar</dc:creator><dc:title>Plantilla de resultats</dc:title></cp:coreProperties>',
        'docProps/app.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>Club Atlètic Castellar</Application></Properties>',
        'xl/workbook.xml' => $workbook,
        'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
        'xl/worksheets/sheet1.xml' => buildResultsSheet(),
        'xl/worksheets/sheet2.xml' => $listSheet,
        'xl/styles.xml' => styles(),
    ];
    writeZip($output, $files);
    echo sprintf("Generada %s amb %d ciutats i %d files de resultats.\n", $output, count($cities), ENTRY_ROWS);
} catch (Throwable $exception) {
    fwrite(STDERR, 'No se ha podido generar la plantilla: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
