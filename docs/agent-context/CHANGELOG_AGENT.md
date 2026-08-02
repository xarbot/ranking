# Changelog para agentes
 
Registro breve de cambios pensados para futuras sesiones de IA. No sustituye al historial de Git.

## 2026-08-02

### Cambiado

- Aniadido apartado admin **Utilidades** con backups JSONL, restore, vaciados selectivos, borrado total de marcas y full reset.
- Incorporado lock de mantenimiento en `storage/locks/` y auditoria admin en `storage/audit/admin-events.ndjson`.
- Aniadida migracion `008_app_settings_seed_control.sql` para persistir `seed_cities_enabled` y evitar que ciudades reaparezcan tras un vaciado explicito.
- Extraida generacion compartida de XLSX a `lib/results_workbook.php` y anadido export de todas las marcas activas en formato multiatleta.
- La importacion masiva de marcas usa jobs temporales en `storage/import-jobs/` para progreso real con polling.
- Aniadida migracion `009_atletas_pendientes.sql`: atletas nuevos sin fecha/sexo se crean como pendientes, sus marcas usan `categoria=NULL`, y los endpoints publicos filtran `estado='completo'`.
- El listado admin de atletas distingue y filtra pendientes; al completar fecha y sexo se recalculan categorias de sus marcas de forma transaccional.
- `api/check-duplicates.php` queda protegido por sesion admin y sigue comparando `categoria` con operador null-safe para pendientes.
- En importaciones de marcas, `Caracteristica tecnica` pasa a ser opcional incluso si la prueba tiene `informacion_adicional`; el alta manual conserva la obligatoriedad.
- La edicion publica de marcas permite cambiar la prueba con el catalogo completo cargado desde `/api/bootstrap`; si la nueva prueba no usa caracteristica tecnica, se limpia en frontend y en altas/ediciones manuales del backend.

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
