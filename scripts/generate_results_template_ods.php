<?php
declare(strict_types=1);

/** Generates the LibreOffice/OpenOffice results workbook with document Basic search macros. */

const ENTRY_ROWS = 500;
const HEADERS = ['Ámbito', 'Grupo', 'Prueba', 'Característica técnica', 'Marca', 'Fecha', 'Ciudad', 'Pista'];

$root = dirname(__DIR__);
$cityFile = $root . '/database/ciudades_es.csv';
$output = $root . '/assets/plantilla-resultados-libreoffice.ods';
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

function x(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function readCities(string $filename): array
{
    $handle = fopen($filename, 'rb');
    if ($handle === false) {
        throw new RuntimeException('No se encuentra database/ciudades_es.csv.');
    }
    fgetcsv($handle, separator: ';');
    $cities = [];
    while (($row = fgetcsv($handle, separator: ';')) !== false) {
        if (trim((string) ($row[0] ?? '')) !== '') {
            $cities[] = trim($row[0]) . (trim((string) ($row[1] ?? '')) !== '' ? ' (' . trim($row[1]) . ')' : '');
        }
    }
    fclose($handle);
    return $cities;
}

function odsCell(string $value = '', string $style = ''): string
{
    $attribute = $style !== '' ? ' table:style-name="' . $style . '"' : '';
    return '<table:table-cell' . $attribute . ' office:value-type="string"><text:p>' . x($value) . '</text:p></table:table-cell>';
}

function odsRow(array $values, string $style = ''): string
{
    $cells = '';
    foreach ($values as $value) {
        $cells .= odsCell((string) $value, $style);
    }
    return '<table:table-row>' . $cells . '</table:table-row>';
}

function contentXml(array $cities, array $catalogue): string
{
    $results = odsRow(HEADERS, 'Header')
        . '<table:table-row table:number-rows-repeated="' . ENTRY_ROWS . '"><table:table-cell table:number-columns-repeated="8"/></table:table-row>';
    $events = odsRow(['Ámbito', 'Grupo', 'Prueba', 'Característica técnica'], 'Header');
    $eventCount = 0;
    foreach ($catalogue as $area => $groups) {
        foreach ($groups as $group => $tests) {
            foreach ($tests as $test) {
                $events .= odsRow([$area, $group, $test, in_array($group, ['Tanques', 'Llançaments'], true) ? 'Sí' : 'No']);
                $eventCount++;
            }
        }
    }
    $cityRows = odsRow(['Ciudad'], 'Header');
    foreach ($cities as $city) {
        $cityRows .= odsRow([$city]);
    }
    $instructions = odsRow(['Plantilla LibreOffice / OpenOffice con macros'], 'Header')
        . odsRow(['1. Habilita las macros al abrir el documento.'])
        . odsRow(['2. En Resultados, selecciona una celda de la fila que quieres completar.'])
        . odsRow(['3. Ejecuta Herramientas > Macros > Ejecutar macro > Standard.Module1.BuscarPrueba o BuscarCiudad.'])
        . odsRow(['4. Guarda solamente la hoja Resultados como CSV antes de importarla.']);
    return '<?xml version="1.0" encoding="UTF-8"?>'
        . '<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0" office:version="1.2">'
        . '<office:automatic-styles><style:style style:name="Header" style:family="table-cell"><style:text-properties fo:font-weight="bold"/><style:table-cell-properties fo:background-color="#306334"/></style:style></office:automatic-styles>'
        . '<office:body><office:spreadsheet>'
        . '<table:table table:name="Resultados"><table:table-column table:number-columns-repeated="8"/>' . $results . '</table:table>'
        . '<table:table table:name="Pruebas"><table:table-column table:number-columns-repeated="4"/>' . $events . '</table:table>'
        . '<table:table table:name="Ciudades"><table:table-column/>' . $cityRows . '</table:table>'
        . '<table:table table:name="Instrucciones"><table:table-column/>' . $instructions . '</table:table>'
        . '<table:database-ranges><table:database-range table:name="FiltroPruebas" table:target-range-address="$Pruebas.$A$1:$D$' . ($eventCount + 1) . '" table:display-filter-buttons="true"/><table:database-range table:name="FiltroCiudades" table:target-range-address="$Ciudades.$A$1:$A$' . (count($cities) + 1) . '" table:display-filter-buttons="true"/></table:database-ranges>'
        . '</office:spreadsheet></office:body></office:document-content>';
}

function macroModule(int $cityCount): string
{
    $basic = <<<'BASIC'
Option Explicit

Function ActiveResultRow() As Long
    On Error GoTo WrongSelection
    Dim sheet As Object
    sheet = ThisComponent.CurrentController.ActiveSheet
    If sheet.Name <> "Resultados" Then
        MsgBox "Selecciona primero una celda de la fila que deseas completar en Resultados."
        ActiveResultRow = -1
        Exit Function
    End If
    ActiveResultRow = ThisComponent.CurrentSelection.CellAddress.Row
    If ActiveResultRow < 1 Or ActiveResultRow > 500 Then GoTo WrongSelection
    Exit Function
WrongSelection:
    MsgBox "Selecciona una fila de entrada entre la 2 y la 501 en Resultados."
    ActiveResultRow = -1
End Function

Sub BuscarPrueba
    Dim targetRow As Long, query As String, choice As Integer
    targetRow = ActiveResultRow()
    If targetRow < 1 Then Exit Sub
    query = LCase(Trim(InputBox("Escribe parte del ambito, grupo o prueba:", "Buscar prueba")))
    If query = "" Then Exit Sub
    choice = ChooseEvent(query)
    If choice < 1 Then Exit Sub
    Dim source As Object, target As Object
    source = ThisComponent.Sheets.getByName("Pruebas")
    target = ThisComponent.Sheets.getByName("Resultados")
    target.getCellByPosition(0, targetRow).String = source.getCellByPosition(0, choice).String
    target.getCellByPosition(1, targetRow).String = source.getCellByPosition(1, choice).String
    target.getCellByPosition(2, targetRow).String = source.getCellByPosition(2, choice).String
End Sub

Function ChooseEvent(query As String) As Integer
    Dim source As Object, i As Long, count As Integer, menu As String, combined As String, answer As String, selected As Integer
    source = ThisComponent.Sheets.getByName("Pruebas")
    For i = 1 To 110
        combined = source.getCellByPosition(0, i).String & " / " & source.getCellByPosition(1, i).String & " / " & source.getCellByPosition(2, i).String
        If InStr(LCase(combined), query) > 0 Then
            count = count + 1
            If count <= 20 Then menu = menu & count & ". " & combined & Chr(10)
        End If
    Next i
    If count = 0 Then MsgBox "No se han encontrado pruebas.": ChooseEvent = -1: Exit Function
    If count > 20 Then MsgBox "Hay mas de 20 resultados. Escribe una busqueda mas concreta.": ChooseEvent = -1: Exit Function
    answer = InputBox(menu & Chr(10) & "Indica el numero de la prueba:", "Escoger prueba")
    selected = Val(answer)
    If selected < 1 Or selected > count Then ChooseEvent = -1: Exit Function
    count = 0
    For i = 1 To 110
        combined = source.getCellByPosition(0, i).String & " / " & source.getCellByPosition(1, i).String & " / " & source.getCellByPosition(2, i).String
        If InStr(LCase(combined), query) > 0 Then
            count = count + 1
            If count = selected Then ChooseEvent = i: Exit Function
        End If
    Next i
    ChooseEvent = -1
End Function

Sub BuscarCiudad
    Dim targetRow As Long, query As String, source As Object, target As Object
    Dim i As Long, count As Integer, menu As String, answer As String, selected As Integer
    targetRow = ActiveResultRow()
    If targetRow < 1 Then Exit Sub
    query = LCase(Trim(InputBox("Escribe parte del nombre de la ciudad:", "Buscar ciudad")))
    If query = "" Then Exit Sub
    source = ThisComponent.Sheets.getByName("Ciudades")
    For i = 1 To __CITY_COUNT__
        If InStr(LCase(source.getCellByPosition(0, i).String), query) > 0 Then
            count = count + 1
            If count <= 20 Then menu = menu & count & ". " & source.getCellByPosition(0, i).String & Chr(10)
        End If
    Next i
    If count = 0 Then MsgBox "No se han encontrado ciudades.": Exit Sub
    If count > 20 Then MsgBox "Hay mas de 20 resultados. Escribe una busqueda mas concreta.": Exit Sub
    answer = InputBox(menu & Chr(10) & "Indica el numero de la ciudad:", "Escoger ciudad")
    selected = Val(answer)
    If selected < 1 Or selected > count Then Exit Sub
    count = 0
    For i = 1 To __CITY_COUNT__
        If InStr(LCase(source.getCellByPosition(0, i).String), query) > 0 Then
            count = count + 1
            If count = selected Then
                target = ThisComponent.Sheets.getByName("Resultados")
                target.getCellByPosition(6, targetRow).String = source.getCellByPosition(0, i).String
                Exit Sub
            End If
        End If
    Next i
End Sub
BASIC;
    $basic = str_replace('__CITY_COUNT__', (string) $cityCount, $basic);
    return '<?xml version="1.0" encoding="UTF-8"?><script:module xmlns:script="http://openoffice.org/2000/script" script:name="Module1" script:language="StarBasic">' . x($basic) . '</script:module>';
}

function writeOds(string $target, array $files): void
{
    $body = '';
    $directory = '';
    $offset = 0;
    foreach ($files as $name => $content) {
        $stored = $name === 'mimetype';
        $compressed = $stored || !function_exists('gzdeflate') ? $content : gzdeflate($content);
        if ($compressed === false) {
            $compressed = $content;
        }
        $method = $stored || $compressed === $content ? 0 : 8;
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
    $package = $body . $directory . pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, strlen($directory), strlen($body), 0);
    if (file_put_contents($target, $package) === false) {
        throw new RuntimeException('No se ha podido guardar la plantilla ODS.');
    }
}

try {
    $cities = readCities($cityFile);
    $files = [
        'mimetype' => 'application/vnd.oasis.opendocument.spreadsheet',
        'content.xml' => contentXml($cities, $catalogue),
        'styles.xml' => '<?xml version="1.0" encoding="UTF-8"?><office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" office:version="1.2"><office:styles/></office:document-styles>',
        'meta.xml' => '<?xml version="1.0" encoding="UTF-8"?><office:document-meta xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:dc="http://purl.org/dc/elements/1.1/" office:version="1.2"><office:meta><dc:title>Plantilla de resultats LibreOffice</dc:title></office:meta></office:document-meta>',
        'Basic/script-lc.xml' => '<?xml version="1.0" encoding="UTF-8"?><library:libraries xmlns:library="http://openoffice.org/2000/library" xmlns:xlink="http://www.w3.org/1999/xlink"><library:library library:name="Standard" library:link="false"/></library:libraries>',
        'Basic/Standard/script-lb.xml' => '<?xml version="1.0" encoding="UTF-8"?><library:library xmlns:library="http://openoffice.org/2000/library" library:name="Standard" library:readonly="false" library:passwordprotected="false"><library:element library:name="Module1"/></library:library>',
        'Basic/Standard/Module1.xml' => macroModule(count($cities)),
        'META-INF/manifest.xml' => '<?xml version="1.0" encoding="UTF-8"?><manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0" manifest:version="1.2"><manifest:file-entry manifest:full-path="/" manifest:media-type="application/vnd.oasis.opendocument.spreadsheet"/><manifest:file-entry manifest:full-path="content.xml" manifest:media-type="text/xml"/><manifest:file-entry manifest:full-path="styles.xml" manifest:media-type="text/xml"/><manifest:file-entry manifest:full-path="meta.xml" manifest:media-type="text/xml"/><manifest:file-entry manifest:full-path="Basic/" manifest:media-type=""/><manifest:file-entry manifest:full-path="Basic/script-lc.xml" manifest:media-type="text/xml"/><manifest:file-entry manifest:full-path="Basic/Standard/" manifest:media-type=""/><manifest:file-entry manifest:full-path="Basic/Standard/script-lb.xml" manifest:media-type="text/xml"/><manifest:file-entry manifest:full-path="Basic/Standard/Module1.xml" manifest:media-type="text/xml"/></manifest:manifest>',
    ];
    writeOds($output, $files);
    echo sprintf("Generada %s amb %d ciutats.\n", $output, count($cities));
} catch (Throwable $exception) {
    fwrite(STDERR, 'No se ha podido generar la plantilla ODS: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
