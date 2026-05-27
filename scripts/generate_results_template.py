#!/usr/bin/env python3
"""Generate the distributable results workbook with catalogue-backed dropdowns."""

from __future__ import annotations

import csv
import re
import unicodedata
import zipfile
from pathlib import Path
from xml.sax.saxutils import escape, quoteattr

ROOT = Path(__file__).resolve().parents[1]
CITY_FILE = ROOT / "database" / "ciudades_es.csv"
OUTPUT = ROOT / "assets" / "plantilla-resultados.xlsx"
ENTRY_ROWS = 500

TRACK_CATALOGUE = {
    "Curses": ["60", "80", "100", "120", "150", "200", "300", "400", "600", "800", "1000", "1500", "Milla", "2000", "3000", "5000", "10000"],
    "Tanques": ["60 mt", "80 mt", "100 mt", "110 mt", "220 mt", "300 mt", "400 mt"],
    "Relleus": ["4x60", "4x80", "4x100", "4x200", "4x300", "4x400", "3x600"],
    "Obstacles": ["1000 sense ria", "1500", "2000", "3000"],
    "Salts": ["Llargada", "Triple", "Alçada", "Perxa"],
    "Llançaments": ["Pes", "Disc", "Javelina", "Martell", "Martell pesat"],
    "Marxa": ["1000", "2000", "3000", "5000", "10000"],
}
CATALOGUE = {
    "Pista Cubierta": TRACK_CATALOGUE,
    "Aire Libre": TRACK_CATALOGUE,
    "Ruta": {
        "Curses": ["Milla", "5km", "10km", "Mitja marató", "Marató"],
        "Marxa": ["1km", "2km", "3km", "5km", "10km", "Mitja marató", "Marató"],
    },
}
HEADERS = ["Ámbito", "Grupo", "Prueba", "Característica técnica", "Marca", "Fecha", "Ciudad", "Pista"]


def safe_name(value: str) -> str:
    plain = unicodedata.normalize("NFD", value).encode("ascii", "ignore").decode("ascii")
    return re.sub(r"[^A-Za-z0-9_]", "_", plain.replace(" ", "_"))


def column(index: int) -> str:
    text = ""
    while index:
        index, remainder = divmod(index - 1, 26)
        text = chr(65 + remainder) + text
    return text


def cell(ref: str, value: str, style: int = 0) -> str:
    return f'<c r="{ref}" s="{style}" t="inlineStr"><is><t>{escape(value)}</t></is></c>'


def row(number: int, values: list[str], style: int = 0) -> str:
    cells = "".join(cell(f"{column(index)}{number}", value, style) for index, value in enumerate(values, 1) if value != "")
    return f'<row r="{number}">{cells}</row>'


def read_cities() -> list[str]:
    with CITY_FILE.open(newline="", encoding="utf-8-sig") as source:
        reader = csv.DictReader(source, delimiter=";")
        return [f'{item["Ciudad"]} ({item["Provincia"]})' for item in reader if item.get("Ciudad")]


def build_lists(cities: list[str]) -> tuple[str, list[tuple[str, str]]]:
    columns: list[list[str]] = [["Ciudad", *cities], ["Ámbito", *CATALOGUE.keys()]]
    ranges = [("Ciudades", f"'Listas'!$A$2:$A${len(cities) + 1}"), ("Ambitos", "'Listas'!$B$2:$B$4")]
    for area, groups in CATALOGUE.items():
        group_values = list(groups.keys())
        columns.append([f"Grupos {area}", *group_values])
        letter = column(len(columns))
        ranges.append((f"Grupos_{safe_name(area)}", f"'Listas'!${letter}$2:${letter}${len(group_values) + 1}"))
        for group, events in groups.items():
            columns.append([f"Pruebas {area} {group}", *events])
            letter = column(len(columns))
            ranges.append((f"Proves_{safe_name(area)}_{safe_name(group)}", f"'Listas'!${letter}$2:${letter}${len(events) + 1}"))
    max_rows = max(len(items) for items in columns)
    rows = []
    for row_index in range(max_rows):
        values = [items[row_index] if row_index < len(items) else "" for items in columns]
        rows.append(row(row_index + 1, values, 1 if row_index == 0 else 0))
    width_tags = "".join(f'<col min="{index}" max="{index}" width="32" customWidth="1"/>' for index in range(1, len(columns) + 1))
    xml = worksheet("".join(rows), width_tags, "")
    return xml, ranges


def validation(kind: str, sqref: str, formula: str, prompt: str, error: str) -> str:
    attrs = f'type="list" allowBlank="0" showInputMessage="1" showErrorMessage="1" errorStyle="stop" sqref="{sqref}" promptTitle={quoteattr(kind)} prompt={quoteattr(prompt)} errorTitle="Valor no válido" error={quoteattr(error)}'
    return f'<dataValidation {attrs}><formula1>{escape(formula)}</formula1></dataValidation>'


def worksheet(rows: str, columns: str, validations: str) -> str:
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' + \
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' + \
        '<sheetViews><sheetView workbookViewId="0"/></sheetViews>' + \
        f'<cols>{columns}</cols><sheetData>{rows}</sheetData>' + \
        '<autoFilter ref="A1:H501"/><sheetProtection sheet="0"/>' + \
        validations + '</worksheet>'


def build_results() -> str:
    validations = [
        validation("Ámbito", "A2:A501", "Ambitos", "Escoge el ámbito.", "Escoge un ámbito de la lista."),
        validation("Grupo", "B2:B501", 'INDIRECT("Grupos_"&SUBSTITUTE(A2," ","_"))', "Primero escoge el ámbito.", "Escoge un grupo válido para el ámbito."),
        validation("Prueba", "C2:C501", 'INDIRECT("Proves_"&SUBSTITUTE(A2," ","_")&"_"&SUBSTITUTE(B2,"ç","c"))', "Primero escoge ámbito y grupo.", "Escoge una prueba válida para el grupo."),
        validation("Ciudad", "G2:G501", "Ciudades", "Escribe para buscar y escoge la ciudad.", "Escoge una ciudad de la lista."),
    ]
    widths = [20, 18, 24, 35, 14, 16, 38, 34]
    cols = "".join(f'<col min="{i}" max="{i}" width="{width}" customWidth="1"/>' for i, width in enumerate(widths, 1))
    return worksheet(row(1, HEADERS, 1), cols, f'<dataValidations count="{len(validations)}">{"".join(validations)}</dataValidations>')


def styles() -> str:
    return '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts>
  <fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF306334"/><bgColor indexed="64"/></patternFill></fill></fills>
  <borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs>
  <cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>'''


def write_workbook() -> None:
    cities = read_cities()
    list_sheet, ranges = build_lists(cities)
    defined = "".join(f'<definedName name="{escape(name)}">{escape(reference)}</definedName>' for name, reference in ranges)
    workbook = f'''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets><sheet name="Resultados" sheetId="1" r:id="rId1"/><sheet name="Listas" sheetId="2" state="hidden" r:id="rId2"/></sheets>
  <definedNames>{defined}</definedNames><calcPr calcId="191029" calcMode="auto"/>
</workbook>'''
    files = {
        '[Content_Types].xml': '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>''',
        '_rels/.rels': '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>''',
        'docProps/core.xml': '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:creator>Club Atlètic Castellar</dc:creator><dc:title>Plantilla de resultats</dc:title></cp:coreProperties>''',
        'docProps/app.xml': '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>Club Atlètic Castellar</Application></Properties>''',
        'xl/workbook.xml': workbook,
        'xl/_rels/workbook.xml.rels': '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>''',
        'xl/worksheets/sheet1.xml': build_results(),
        'xl/worksheets/sheet2.xml': list_sheet,
        'xl/styles.xml': styles(),
    }
    OUTPUT.parent.mkdir(exist_ok=True)
    with zipfile.ZipFile(OUTPUT, 'w', zipfile.ZIP_DEFLATED) as package:
        for name, content in files.items():
            package.writestr(name, content.encode('utf-8'))
    print(f"Generated {OUTPUT} with {len(cities)} cities and {ENTRY_ROWS} result rows.")


if __name__ == "__main__":
    write_workbook()
