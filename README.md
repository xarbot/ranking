# Ranking de Atletismo

Aplicacion de mejores marcas de atletismo con consulta publica en `/`, panel de gestion
autenticado en `/admin/`, persistencia MySQL y categoria calculada por edad.

Tanto la consulta publica como el panel de gestion permiten alternar castellano y catalan.

## Requisitos

- PHP 8.3 con la extension `pdo_mysql`.
- MySQL 8.0 o compatible.
- nginx con PHP 8.3 y el fragmento de rutas incluido en `deploy/nginx/`.

## Base de datos

Crea las tablas solo en una base de datos nueva ya provisionada para la aplicacion:

```sh
mysql --user=ranking_app --password ranking_atletismo < database/init.sql
```

La creacion de la base y del usuario MySQL se detalla en [`DEPLOY.md`](DEPLOY.md).
Las instalaciones existentes se actualizan mediante los SQL incrementales de
`database/migrations/`, ejecutados por `php scripts/migrate.php`.

El esquema contiene:

- `usuarios`: cuentas con contrasena cifrada para acceder a la gestion de datos.
- `atletas`: nombre, apellidos y fecha de nacimiento.
- `pistas`: instalaciones y localidad.
- `pruebas`: catalogo de pruebas y criterio de ranking (`menor` para tiempos o `mayor` para saltos/lanzamientos).
- `marcas`: atleta, prueba, pista, fecha, resultado y categoria, con claves foraneas.

## Despliegue nginx

1. Publica el repositorio en el `root` nginx; `/` sirve los listados y `/admin/` la gestion.
2. Copia `.env.example` como `.env` en el directorio padre de la raiz publica.
3. Edita `.env` con `DB_HOST`, `DB_NAME`, `DB_USER` y `DB_PASS` del servidor.
4. Instala `deploy/nginx/ranking.conf.example` en el directorio de includes del virtual host y recarga nginx tras ejecutar `nginx -t`.
5. Accede a `/admin/` para crear el primer usuario de gestion.

La ruta `/` es publica y presenta primero las ultimas marcas introducidas. Al seleccionar un
atleta muestra su historial completo, agrupado por categoria y prueba; dentro de cada prueba
ordena de mejor a peor segun gane el resultado menor o mayor.
Los desplegables permiten obtener rankings por prueba, por categoria o combinando ambas. Cada
prueba presenta las 20 mejores marcas de atletas, ordenadas segun su criterio, y permite cargar
20 resultados mas cuando existen. El nombre del atleta enlaza igualmente con su historial.
La ruta `/admin/` requiere autenticacion para modificar datos y el criterio de cada prueba.
La consulta utiliza `GET /api/public/marks`, `GET /api/public/ranking` y
`GET /api/public/athletes/{id}/history`, que no devuelven usuarios ni fechas de nacimiento.

`.env` contiene credenciales y queda fuera del webroot. Las interfaces llaman a `api/...`;
nginx redirige esas peticiones al controlador `api/index.php`.

Para instalacion y actualizacion en produccion, sigue [`DEPLOY.md`](DEPLOY.md).

## Uso

1. Consulta los listados abiertos desde `/`; no exponen usuarios ni fechas de nacimiento.
2. Inicia sesion desde `/admin/` con un usuario de gestion.
3. Desde **Usuarios**, crea, edita, activa o desactiva las cuentas necesarias.
4. Crea las pistas de atletismo.
5. Registra atletas manualmente o importalos desde CSV usando la plantilla descargable.
6. Define las pruebas y su criterio de ranking.
7. Introduce marcas y revisalas desde el panel de gestion.

La categoria se calcula en la API segun la edad cumplida del atleta en la fecha de la
marca: `sub8`, `sub10`, `sub12`, `sub14`, `sub16`, `sub18`, `sub20`, `sub23`,
`senior` (23 a 34 anos) y `master` (desde 35 anos).

## Importacion de atletas

El CSV puede estar separado por punto y coma o coma y debe incluir las columnas `Nombre`,
`Apellidos` y `Fecha de nacimiento`. Las fechas admitidas son `AAAA-MM-DD` y `DD/MM/AAAA`.

Los datos introducidos en versiones anteriores que usaban `localStorage` no se migran
automaticamente a MySQL.
