# Arquitectura

## Estructura conocida

- `/index.html`, `/app.js`, `/styles.css`: consulta publica del ranking.
- `/admin/index.html`, `/admin/app.js`, `/admin/styles.css`: panel de gestion autenticado.
- `/api/index.php`: API principal en PHP.
- `/lib/env.php`: lectura de configuracion de entorno.
- `/database/init.sql`: esquema inicial.
- `/database/migrations/`: migraciones incrementales.
- `/database/ciudades_es.csv`: catalogo inicial de ciudades.
- `/scripts/`: utilidades PHP.
- `/assets/`: logos y plantillas para importacion.
- `/deploy/nginx/`: ejemplo de configuracion nginx.

## Produccion

- Produccion solo garantiza PHP.
- La base de datos soportada es MySQL.
- Mantener compatibilidad con hosting PHP estandar.
- No asumir Docker en produccion.
- No asumir Node, builders, colas, workers, procesos residentes ni servicios externos en produccion sin confirmacion explicita.
- No cambiar configuracion de produccion salvo peticion explicita.

## Configuracion sensible

La configuracion sensible se lee desde `.env` fuera del webroot o desde el mecanismo definido por el despliegue. No modificar ni exponer credenciales, secretos, backups, dumps o datos privados.
