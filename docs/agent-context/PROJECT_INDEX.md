# Indice de proyecto para agentes

Entrada obligatoria para ahorrar contexto. Lee este archivo primero y abre solo lo necesario segun la tarea.

## Lectura minima

- Siempre: `AGENTS.md` y este `PROJECT_INDEX.md`.
- Contexto general: `PROJECT_CONTEXT.md`.
- Codigo o rutas: `ARCHITECTURE.md`.
- Base de datos o datos deportivos: `DATA_MODEL.md`.
- Procedimientos: `WORKFLOWS.md`.
- Normas de edicion: `CODING_RULES.md`.
- Riesgos antes de tocar zonas delicadas: `KNOWN_ISSUES.md`.
- Memoria de cambios de agentes: `CHANGELOG_AGENT.md`.

## Segun la tarea

- Cambios publicos en `/`: lee `PROJECT_CONTEXT.md`, `ARCHITECTURE.md`, `CODING_RULES.md`; busca en `index.html`, `app.js`, `styles.css`, `i18n.js`.
- Cambios de administracion: lee `ARCHITECTURE.md`, `WORKFLOWS.md`, `CODING_RULES.md`; busca en `admin/` y endpoints de `api/index.php`.
- API PHP: lee `ARCHITECTURE.md`, `DATA_MODEL.md`, `CODING_RULES.md`, `KNOWN_ISSUES.md`; busca funciones/rutas en `api/index.php`.
- MySQL o migraciones: lee `DATA_MODEL.md`, `WORKFLOWS.md`, `KNOWN_ISSUES.md`; revisa `database/init.sql` y la ultima migracion necesaria.
- Importaciones de resultados: lee `WORKFLOWS.md`, `DATA_MODEL.md`, `KNOWN_ISSUES.md`; busca `importMarks`, `importMultipleMarks`, plantillas y scripts.
- Rankings/categorias: lee `PROJECT_CONTEXT.md`, `DATA_MODEL.md`, `KNOWN_ISSUES.md`; busca `categoryForDates`, `publicRanking`, `comparableResult`.
- Atletas, duplicados o permisos: lee `DATA_MODEL.md`, `WORKFLOWS.md`, `KNOWN_ISSUES.md`; busca funciones `move`, `permissions`, `usuarios`.
- Ciudades o pistas: lee `DATA_MODEL.md`, `WORKFLOWS.md`, `KNOWN_ISSUES.md`; revisa `database/ciudades_es.csv` solo si es imprescindible.
- Traducciones/textos visibles: lee `PROJECT_CONTEXT.md`, `CODING_RULES.md`; busca en `i18n.js`, `admin/i18n.js` y tabla `traducciones`.
- Despliegue: lee `PROJECT_CONTEXT.md`, `ARCHITECTURE.md`, `WORKFLOWS.md`; consulta `DEPLOY.md` solo para pasos concretos.
- Documentacion: lee este indice, `PROJECT_CONTEXT.md`, `CODING_RULES.md`, `CHANGELOG_AGENT.md`.

## Como buscar

- Usa `rg --files` para localizar archivos.
- Usa `rg "texto|funcion|endpoint"` antes de abrir archivos grandes.
- En `api/index.php`, busca funciones o rutas concretas; evita leerlo entero si no hace falta.
- En SQL, revisa primero `database/init.sql` y despues solo migraciones relacionadas.
- En assets grandes, evita abrir binarios o plantillas completas.

## Archivos que no debes tocar sin peticion explicita

- `.env`, credenciales, backups, dumps y datos sensibles.
- Configuracion de produccion.
- Migraciones ya aplicadas, salvo que el usuario pida corregir historia local.
- Plantillas binarias de `assets/` salvo tarea especifica.
- Datos masivos como `database/ciudades_es.csv` salvo importacion/catalogo.

## Compatibilidad

- Produccion: PHP + MySQL en hosting estandar.
- No asumir Docker, Node, builders, colas, workers ni servicios externos en produccion.
- Mantener cambios pequenos y reversibles.

## Documentos antiguos

Algunos nombres historicos pueden seguir existiendo por compatibilidad (`WORKFLOW.md`, `CODING_STANDARDS.md`, `CHANGELOG_AI.md`, `docs/ai/`). La fuente actual es este directorio y, para reglas nuevas, los documentos listados arriba.

## Herramientas MCP

Ver:

- `docs/agent-context/MCP_SERVERS.md`

Este archivo documenta los servidores MCP disponibles para agentes locales/remotos, su ambito y el uso recomendado de:
`ranking-filesystem`, `ranking-tools`, `ranking-git` y `ranking-qdrant`.
