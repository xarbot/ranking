# Changelog para agentes
 
Registro breve de cambios pensados para futuras sesiones de IA. No sustituye al historial de Git.

## 2026-08-02

### Cambiado

- Aniadido apartado admin **Utilidades** con backups JSONL, restore, vaciados selectivos, borrado total de marcas y full reset.
- Incorporado lock de mantenimiento en `storage/locks/` y auditoria admin en `storage/audit/admin-events.ndjson`.
- Aniadida migracion `008_app_settings_seed_control.sql` para persistir `seed_cities_enabled` y evitar que ciudades reaparezcan tras un vaciado explicito.
- Extraida generacion compartida de XLSX a `lib/results_workbook.php` y anadido export de todas las marcas activas en formato multiatleta.
- La importacion masiva de marcas usa jobs temporales en `storage/import-jobs/` para progreso real con polling.

### Notas

- No se han ejecutado operaciones destructivas ni backups/restores reales durante la implementacion.

## 2026-06-06

### Cambiado

- Reorganizada la documentacion neutral de agentes alrededor de `PROJECT_INDEX.md`.
- Aniadidos documentos compactos: `DATA_MODEL.md`, `WORKFLOWS.md`, `CODING_RULES.md`, `KNOWN_ISSUES.md`.
- Actualizado `AGENTS.md` para priorizar lectura selectiva, busqueda/indexacion y bajo consumo de contexto.
- Dejados archivos historicos como compatibilidad cuando duplicaban nombres antiguos.

### Notas

- No se ha modificado codigo funcional.
- No se han tocado credenciales, datos, migraciones ni configuracion de produccion.
