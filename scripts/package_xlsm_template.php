<?php
declare(strict_types=1);

/** Packages the Microsoft templates as macro-enabled workbooks. */

$root = dirname(__DIR__);
$vbaProject = $root . '/assets/vbaProject.bin';
$templates = [
    [
        $root . '/assets/plantilla-resultados-microsoft.xlsx',
        $root . '/assets/plantilla-resultados-microsoft-2021.xlsm',
    ],
    [
        $root . '/assets/plantilla-resultados-atletas-microsoft.xlsx',
        $root . '/assets/plantilla-resultados-atletas-microsoft-2021.xlsm',
    ],
];

function readZipFiles(string $source): array
{
    if (!is_file($source)) {
        throw new RuntimeException('No se encuentra ' . $source . '.');
    }
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($source) !== true) {
            throw new RuntimeException('No se ha podido abrir ' . $source . '.');
        }
        $files = [];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            if ($name === false) {
                continue;
            }
            $content = $zip->getFromIndex($index);
            if ($content === false) {
                throw new RuntimeException('No se ha podido leer ' . $name . '.');
            }
            $files[$name] = $content;
        }
        $zip->close();
        return $files;
    }

    return readZipFilesManually($source);
}

function readZipFilesManually(string $source): array
{
    $data = file_get_contents($source);
    if ($data === false) {
        throw new RuntimeException('No se ha podido leer ' . $source . '.');
    }
    $end = strrpos($data, "PK\x05\x06");
    if ($end === false) {
        throw new RuntimeException($source . ' no parece un ZIP valido.');
    }
    $eocd = unpack('vdisk/vstartDisk/ventriesDisk/ventries/VdirectorySize/VdirectoryOffset/vcommentLength', substr($data, $end + 4, 18));
    $offset = (int) $eocd['directoryOffset'];
    $entries = (int) $eocd['entries'];
    $files = [];

    for ($entry = 0; $entry < $entries; $entry++) {
        if (substr($data, $offset, 4) !== "PK\x01\x02") {
            throw new RuntimeException('Directorio ZIP corrupto en ' . $source . '.');
        }
        $header = unpack('vversionMade/vversionNeeded/vflags/vmethod/vtime/vdate/Vcrc/VcompressedSize/Vsize/vnameLength/vextraLength/vcommentLength/vdisk/vinternal/Vexternal/VlocalOffset', substr($data, $offset + 4, 42));
        $nameLength = (int) $header['nameLength'];
        $extraLength = (int) $header['extraLength'];
        $commentLength = (int) $header['commentLength'];
        $name = substr($data, $offset + 46, $nameLength);
        $localOffset = (int) $header['localOffset'];

        if (substr($data, $localOffset, 4) !== "PK\x03\x04") {
            throw new RuntimeException('Entrada ZIP corrupta: ' . $name . '.');
        }
        $local = unpack('vversion/vflags/vmethod/vtime/vdate/Vcrc/VcompressedSize/Vsize/vnameLength/vextraLength', substr($data, $localOffset + 4, 26));
        $payloadOffset = $localOffset + 30 + (int) $local['nameLength'] + (int) $local['extraLength'];
        $compressed = substr($data, $payloadOffset, (int) $header['compressedSize']);
        $method = (int) $header['method'];
        if ($method === 0) {
            $content = $compressed;
        } elseif ($method === 8) {
            $content = gzinflate($compressed);
            if ($content === false) {
                throw new RuntimeException('No se ha podido descomprimir ' . $name . '.');
            }
        } else {
            throw new RuntimeException('Metodo ZIP no soportado en ' . $name . ': ' . $method . '.');
        }
        $files[$name] = $content;
        $offset += 46 + $nameLength + $extraLength + $commentLength;
    }

    return $files;
}

function addContentType(string $xml): string
{
    $xml = str_replace(
        'ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"',
        'ContentType="application/vnd.ms-excel.sheet.macroEnabled.main+xml"',
        $xml
    );
    if (!str_contains($xml, '/xl/vbaProject.bin')) {
        $xml = str_replace(
            '</Types>',
            '<Override PartName="/xl/vbaProject.bin" ContentType="application/vnd.ms-office.vbaProject"/></Types>',
            $xml
        );
    }
    return $xml;
}

function addWorkbookRelationship(string $xml): string
{
    if (str_contains($xml, 'Target="vbaProject.bin"')) {
        return $xml;
    }
    $relationship = '<Relationship Id="rIdVbaProject" Type="http://schemas.microsoft.com/office/2006/relationships/vbaProject" Target="vbaProject.bin"/>';
    return str_replace('</Relationships>', $relationship . '</Relationships>', $xml);
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
    if (!is_file($vbaProject)) {
        throw new RuntimeException('Falta assets/vbaProject.bin. Crea este binario una vez con Excel 2021 y vuelve a ejecutar este script.');
    }
    $vba = file_get_contents($vbaProject);
    if ($vba === false) {
        throw new RuntimeException('No se ha podido leer assets/vbaProject.bin.');
    }

    foreach ($templates as [$source, $target]) {
        $files = readZipFiles($source);
        $files['[Content_Types].xml'] = addContentType($files['[Content_Types].xml']);
        $files['xl/_rels/workbook.xml.rels'] = addWorkbookRelationship($files['xl/_rels/workbook.xml.rels']);
        $files['xl/vbaProject.bin'] = $vba;
        writeZip($target, $files);
        echo 'Generada ' . $target . PHP_EOL;
    }
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
