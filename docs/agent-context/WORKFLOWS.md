# Flujos de trabajo

## Importar marcas

- Revisar formato esperado en `README.md` solo si la tarea trata plantillas o CSV.
- En admin hay flujo individual por atleta y flujo multiatleta con columna `Atleta`.
- Validar todas las filas antes de grabar; si hay errores, no importar parcialmente.
- La importacion masiva usa jobs temporales en `storage/import-jobs/` para progreso y un lock en `storage/locks/`.
- Comprobar atleta, prueba, ciudad, fecha y marca. En importacion, la caracteristica tecnica es opcional aunque la prueba tenga `informacion_adicional`.
- Si un atleta nuevo tiene nombre/apellidos validos pero falta fecha de nacimiento o sexo, se crea como `estado='pendiente'` y sus marcas entran con `categoria=NULL`; esto no cuenta como error masivo.
- La creacion de atletas nuevos y la insercion de marcas deben permanecer en la misma transaccion de importacion.
- Para cambios de logica, buscar `importMarks`, `importMultipleMarks`, `markPayload`, `requiredResult`.

## Utilidades admin

- Backups logicos en `storage/backups/` con formato JSONL propio; no dependen de `mysqldump` ni `ZipArchive`.
- `schema_migrations` se valida como metadata estricta, pero no se restaura como tabla de datos.
- Restaurar, borrar todas las marcas y full reset crean backup previo y usan lock de mantenimiento.
- No ejecutar restore, vaciados ni borrados sobre una DB real salvo peticion explicita.

## Revisar duplicados

- Atletas: no fusionar sin confirmacion; `moveAthleteMarks` reasigna marcas activas y papelera al destino y elimina el atleta origen.
- Ciudades: preferir fusion segura si hay marcas asociadas; buscar `mergeCities` y `writeCity`.
- Pistas: revisar `tracks`, `writeTrack`, `upsertTrack` antes de tocar.
- Marcas: los borrados son suaves hacia `marcas_borradas`; buscar `softDeleteMark`.

## Generar rankings

- Revisar `publicRanking`, `comparableResult`, `categoryForDates` y `refreshCategories`.
- Mantener separacion por sexo y categoria.
- Respetar `sentido_resultado`: menor gana en carreras, mayor gana en saltos/lanzamientos.
- La parte publica solo debe mostrar ambitos, grupos, pruebas, categorias y atletas completos con marcas. Aplicar siempre filtro servidor `atletas.estado = 'completo'`.

## Modificar utilidades admin

- Leer `ARCHITECTURE.md`, `DATA_MODEL.md`, `CODING_RULES.md`.
- Buscar en `admin/app.js` por el nombre del formulario, boton, endpoint o estado afectado.
- Si se toca texto visible, revisar `admin/i18n.js` y `traducciones` si aplica.
- Mantener permisos: admin, gestor de marcas y usuario normal con permisos por atleta.

## Subir version

- Si el cambio es publicable y afecta a la aplicacion desplegada, incrementar +0.1 donde corresponda.
- Revisar `README.md` y los pies/version visible si existen.
- Actualizar cache busters `?v=` solo cuando cambien assets servidos al navegador.
- Para cambios solo documentales, no subir version salvo peticion explicita.

## Validar cambios

- Documentacion: revisar enlaces/rutas y hacer `git diff --check` si procede.
- PHP: ejecutar `php -l` sobre archivos tocados.
- Migraciones: revisar SQL y probar en entorno seguro antes de produccion.
- Plantillas: regenerar con `php scripts/generate_results_template.php` si cambia catalogo/ciudades usados por Excel.
- Produccion: aplicar backup antes de migraciones o cambios de datos.
