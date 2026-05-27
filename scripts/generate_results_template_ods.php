<?php
declare(strict_types=1);

/** Generates LibreOffice/OpenOffice result templates with standard dropdown validations. */

const ENTRY_ROWS = 500;
const HEADERS = ['Ámbito / Grupo', 'Prueba', 'Característica técnica', 'Marca', 'Fecha', 'Ciudad', 'Pista'];

$root = dirname(__DIR__);
$cityFile = $root . '/database/ciudades_es.csv';
$output = $root . '/assets/plantilla-resultados-libreoffice.ods';
$multiOutput = $root . '/assets/plantilla-resultados-atletas-libreoffice.ods';
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
    'Ruta' => ['Curses' => ['Milla', '5km', '10km', 'Mitja marató', 'Marató'], 'Marxa' => ['1km', '2km', '3km', '5km', '10km', 'Mitja marató', 'Marató']],
];

function x(string $value): string { return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8'); }
function safeName(string $value): string { $plain=strtr($value,['ç'=>'c','Ç'=>'C','à'=>'a','á'=>'a','è'=>'e','é'=>'e','í'=>'i','ò'=>'o','ó'=>'o','ú'=>'u']); return preg_replace('/[^A-Za-z0-9_]/','_',str_replace(' ','_',$plain)) ?? $plain; }
function column(int $index): string { $text=''; while($index>0){$rest=($index-1)%26;$text=chr(65+$rest).$text;$index=intdiv($index-1,26);} return $text; }
function readCities(string $file): array { $h=fopen($file,'rb'); if(!$h) throw new RuntimeException('No se encuentra database/ciudades_es.csv.'); fgetcsv($h, separator:';'); $out=[]; while(($r=fgetcsv($h, separator:';'))!==false){if(trim((string)($r[0]??''))!=='')$out[]=trim($r[0]).(trim((string)($r[1]??''))!==''?' ('.trim($r[1]).')':'');} fclose($h); return $out; }
function odsCell(string $value='', string $style='', string $validation=''): string { $a=$style!==''?' table:style-name="'.$style.'"':''; $a.=$validation!==''?' table:content-validation-name="'.$validation.'"':''; return '<table:table-cell'.$a.' office:value-type="string"><text:p>'.x($value).'</text:p></table:table-cell>'; }
function odsRow(array $values, string $style=''): string { $cells=''; foreach($values as $v)$cells.=odsCell((string)$v,$style); return '<table:table-row>'.$cells.'</table:table-row>'; }
function conditionValues(array $values): string { return 'of:cell-content-is-in-list('.implode(';',array_map(static fn($v):string=>'&quot;'.x((string)$v).'&quot;',$values)).')'; }
function contentXml(array $cities, array $catalogue, bool $includeAthlete): string {
    $scopes=[]; $eventColumns=[]; $eventRows=odsRow(['Ámbito / Grupo','Prueba','Característica técnica'],'Header'); $count=0;
    foreach($catalogue as $area=>$groups){foreach($groups as $group=>$events){$scope=$area.' / '.$group;$scopes[]=$scope;$eventColumns[]=[$scope,$events];foreach($events as $event){$eventRows.=odsRow([$scope,$event,in_array($group,['Tanques','Llançaments'],true)?'Sí':'No']);$count++;}}}
    $listColumns=[['Ciudad',...$cities],['Ámbito / Grupo',...$scopes]]; foreach($eventColumns as [$scope,$events])$listColumns[]=['Pruebas '.$scope,...$events];
    $rows=max(array_map('count',$listColumns));$listRows='';for($i=0;$i<$rows;$i++){$values=[];foreach($listColumns as $items)$values[]=$items[$i]??'';$listRows.=odsRow($values,$i===0?'Header':'');}
    $names='<table:named-expressions><table:named-range table:name="Ambitos_Grupos" table:cell-range-address="$Listas.$B$2:.$B$'.(count($scopes)+1).'" table:base-cell-address="$Listas.$B$2"/>';
    foreach($eventColumns as $position=>[$scope,$events]){$letter=column($position+3);$names.='<table:named-range table:name="'.x('Proves_'.safeName($scope)).'" table:cell-range-address="$Listas.$'.$letter.'$2:.$'.$letter.'$'.(count($events)+1).'" table:base-cell-address="$Listas.$'.$letter.'$2"/>';}$names.='</table:named-expressions>';
    $headers=$includeAthlete?['Atleta',...HEADERS]:HEADERS;$scopeCell=$includeAthlete?'[.$B2]':'[.$A2]';
    $blank=$includeAthlete?odsCell():'';$blank.=odsCell('','','AmbitosGrupos').odsCell('','','Pruebas').odsCell().odsCell().odsCell().odsCell('','','Ciudades').odsCell();
    $results=odsRow($headers,'Header').'<table:table-row table:number-rows-repeated="'.ENTRY_ROWS.'">'.$blank.'</table:table-row>';
    $cityRows=odsRow(['Ciudad'],'Header'); foreach($cities as $city)$cityRows.=odsRow([$city]);
    $instructions=odsRow(['Plantilla con desplegables dependientes'],'Header').odsRow(['Selecciona Ámbito / Grupo; la lista de Prueba mostrará únicamente sus pruebas.']).odsRow(['La fecha admite AAAA-MM-DD, AAAA/MM/DD, DD-MM-AAAA y DD/MM/AAAA.']).odsRow(['Rellena únicamente la hoja Resultados y guárdala como CSV antes de importarla.']);
    $eventCondition='of:cell-content-is-in-list(INDIRECT(&quot;Proves_&quot;&amp;SUBSTITUTE(SUBSTITUTE(SUBSTITUTE('.$scopeCell.';&quot; &quot;;&quot;_&quot;);&quot;/&quot;;&quot;_&quot;);&quot;ç&quot;;&quot;c&quot;)))';
    $valid='<table:content-validations>'
        .'<table:content-validation table:name="AmbitosGrupos" table:condition="of:cell-content-is-in-list([$Listas.$B$2:.$B$'.(count($scopes)+1).'])" table:allow-empty-cell="true" table:display-list="unsorted"/>'
        .'<table:content-validation table:name="Pruebas" table:condition="'.$eventCondition.'" table:allow-empty-cell="true" table:display-list="unsorted"/>'
        .'<table:content-validation table:name="Ciudades" table:condition="of:cell-content-is-in-list([$Ciudades.$A$2:.$A$'.(count($cities)+1).'])" table:allow-empty-cell="true" table:display-list="unsorted"/>'
        .'</table:content-validations>';
    return '<?xml version="1.0" encoding="UTF-8"?>'
        .'<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0" xmlns:of="urn:oasis:names:tc:opendocument:xmlns:of:1.2" office:version="1.2">'
        .'<office:automatic-styles><style:style style:name="Header" style:family="table-cell"><style:text-properties fo:font-weight="bold"/><style:table-cell-properties fo:background-color="#306334"/></style:style></office:automatic-styles><office:body><office:spreadsheet>'.$valid.$names
        .'<table:table table:name="Resultados"><table:table-column table:number-columns-repeated="'.($includeAthlete?8:7).'"/>'.$results.'</table:table>'
        .'<table:table table:name="Listas" table:display="false"><table:table-column table:number-columns-repeated="'.count($listColumns).'"/>'.$listRows.'</table:table>'
        .'<table:table table:name="Pruebas"><table:table-column table:number-columns-repeated="3"/>'.$eventRows.'</table:table>'
        .'<table:table table:name="Ciudades"><table:table-column/>'.$cityRows.'</table:table>'
        .'<table:table table:name="Instrucciones"><table:table-column/>'.$instructions.'</table:table>'
        .'<table:database-ranges><table:database-range table:name="FiltroPruebas" table:target-range-address="$Pruebas.$A$1:$C$'.($count+1).'" table:display-filter-buttons="true"/><table:database-range table:name="FiltroCiudades" table:target-range-address="$Ciudades.$A$1:$A$'.(count($cities)+1).'" table:display-filter-buttons="true"/></table:database-ranges>'
        .'</office:spreadsheet></office:body></office:document-content>';
}
function writeOds(string $target, string $content): void {
    $files=['mimetype'=>'application/vnd.oasis.opendocument.spreadsheet','content.xml'=>$content,'styles.xml'=>'<?xml version="1.0" encoding="UTF-8"?><office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" office:version="1.2"><office:styles/></office:document-styles>','meta.xml'=>'<?xml version="1.0" encoding="UTF-8"?><office:document-meta xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" office:version="1.2"><office:meta/></office:document-meta>','META-INF/manifest.xml'=>'<?xml version="1.0" encoding="UTF-8"?><manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0" manifest:version="1.2"><manifest:file-entry manifest:full-path="/" manifest:media-type="application/vnd.oasis.opendocument.spreadsheet"/><manifest:file-entry manifest:full-path="content.xml" manifest:media-type="text/xml"/><manifest:file-entry manifest:full-path="styles.xml" manifest:media-type="text/xml"/><manifest:file-entry manifest:full-path="meta.xml" manifest:media-type="text/xml"/></manifest:manifest>'];
    $body='';$dir='';$offset=0;foreach($files as $name=>$data){$store=$name==='mimetype';$compressed=$store||!function_exists('gzdeflate')?$data:gzdeflate($data);if($compressed===false)$compressed=$data;$method=$store||$compressed===$data?0:8;$crc=crc32($data);$size=strlen($data);$zsize=strlen($compressed);$length=strlen($name);$local=pack('VvvvvvVVVvv',0x04034b50,20,0,$method,0,0,$crc,$zsize,$size,$length,0).$name.$compressed;$dir.=pack('VvvvvvvVVVvvvvvVV',0x02014b50,20,20,0,$method,0,0,$crc,$zsize,$size,$length,0,0,0,0,0,$offset).$name;$body.=$local;$offset+=strlen($local);} $number=count($files);$zip=$body.$dir.pack('VvvvvVVv',0x06054b50,0,0,$number,$number,strlen($dir),strlen($body),0);if(file_put_contents($target,$zip)===false)throw new RuntimeException('No se ha podido guardar '.$target.'.');
}
try { $cities=readCities($cityFile); writeOds($output,contentXml($cities,$catalogue,false)); writeOds($multiOutput,contentXml($cities,$catalogue,true)); echo sprintf("Generades %s i %s amb %d ciutats.\n",$output,$multiOutput,count($cities)); } catch(Throwable $e){fwrite(STDERR,'No se han podido generar las plantillas ODS: '.$e->getMessage().PHP_EOL);exit(1);}
