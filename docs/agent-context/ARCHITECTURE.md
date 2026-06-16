# Arquitectura
 
## Mapa de carpetas

- `/index.html`, `/app.js`, `/styles.css`, `/i18n.js`: consulta publica de rankings y marcas.
- `/admin/index.html`, `/admin/app.js`, `/admin/styles.css`, `/admin/i18n.js`: panel autenticado de gestion.
- `/api/index.php`: controlador unico de API, sesiones, validaciones, rankings, importaciones y CRUD.
- `/lib/env.php`: lectura de variables de entorno desde `.env`.
- `/database/init.sql`: esquema completo para instalaciones nuevas.
- `/database/migrations/`: migraciones incrementales aplicadas por `scripts/migrate.php`.
- `/database/ciudades_es.csv`: catalogo inicial de municipios; archivo grande.
- `/scripts/`: migraciones y generacion/empaquetado de plantillas.
- `/assets/`: logo y plantillas Excel/LibreOffice para importaciones.
- `/deploy/nginx/`: ejemplo de rutas nginx.
- `/storage/`: ruta reservada con `.gitignore`.
- `/docs/agent-context/`: contexto neutral para agentes.
- `/docs/ai/`: documentacion historica; usar solo como compatibilidad.

## Modulos principales

- Publico: carga traducciones, marcas recientes, filtros y rankings desde endpoints `/api/public/...`.
- Administracion: gestiona login, atletas, pruebas, ciudades, pistas, marcas, usuarios, permisos, traducciones e importaciones.
- API: usa transacciones por peticion, valida payloads, aplica permisos y responde JSON.
- Datos: MySQL con claves foraneas, migraciones versionadas en `schema_migrations`.
- Plantillas: `scripts/generate_results_template.php` regenera Excel tras cambios de catalogo.

## Archivos principales

- `api/index.php`: primero busca la funcion o ruta concreta (`route`, `publicRanking`, `writeMark`, `importMarks`, etc.).
- `database/init.sql`: fuente compacta del modelo actual.
- `README.md`: descripcion de uso, version y despliegue resumido.
- `DEPLOY.md`: pasos detallados de despliegue nginx/PHP.
- `docs/agent-context/PROJECT_INDEX.md`: entrada obligatoria para agentes.

## Produccion y limites

- Produccion solo garantiza PHP + MySQL.
- No introducir dependencias obligatorias de build ni servicios residentes.
- No modificar `.env`, secretos, backups, dumps ni configuracion de produccion salvo peticion explicita.
