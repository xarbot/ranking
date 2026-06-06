# Problemas conocidos y riesgos

## Documentacion

- `docs/ai/` es historico; la fuente actual esta en `docs/agent-context/`.
- Algunos documentos antiguos usaban `WORKFLOW.md`, `CODING_STANDARDS.md` y `CHANGELOG_AI.md`; ahora son compatibilidad.
- La documentacion antigua menciona competiciones, pero no hay tabla de competiciones en `database/init.sql`.

## Zonas delicadas

- `api/index.php` concentra muchas responsabilidades; buscar funcion/ruta concreta antes de editar.
- Categorias y rankings dependen de `categoryForDates`, `refreshCategories`, `publicRanking` y `sentido_resultado`.
- Importaciones deben validar todo antes de escribir; evitar grabaciones parciales.
- Permisos combinan roles globales y permisos por atleta; revisar antes de tocar marcas o atletas.
- `marcas_borradas` conserva marcas eliminadas; no purgar sin confirmacion clara.
- `database/ciudades_es.csv` es grande y se carga como catalogo inicial; no editarlo salvo tarea especifica.
- Plantillas Excel de `assets/` incluyen binarios; no abrir ni regenerar sin necesidad.

## Antes de tocar

- Comprobar `git status --short`.
- Revisar si hay cambios del usuario en los archivos afectados.
- Buscar referencias con `rg` antes de editar nombres, endpoints, campos o literales.
- Si hay migracion o datos reales, pedir confirmacion y recomendar backup.
- Si un dato deportivo es ambiguo, marcar para revision; no inventar.

## Produccion

- Solo PHP + MySQL garantizados.
- `.env` esta fuera del webroot en produccion.
- No asumir Docker ni procesos residentes.
- No cambiar nginx/despliegue sin peticion explicita.
