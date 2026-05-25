# Ranking de Atletismo

Aplicacion de entrada de marcas de atletismo con persistencia MySQL y categoria
calculada automaticamente por edad.

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
- `pruebas`: catalogo de pruebas.
- `marcas`: atleta, prueba, pista, fecha, resultado y categoria, con claves foraneas.

## Despliegue nginx

1. Publica el repositorio en el `root` nginx; `index.html` y `api/` se sirven desde la raiz.
2. Copia `.env.example` como `.env` en el directorio padre de la raiz publica.
3. Edita `.env` con `DB_HOST`, `DB_NAME`, `DB_USER` y `DB_PASS` del servidor.
4. Instala `deploy/nginx/ranking.conf.example` en el directorio de includes del virtual host y recarga nginx tras ejecutar `nginx -t`.
5. Accede a la URL configurada para la aplicacion.

En la primera apertura, la aplicacion solicita crear el primer usuario de gestion.

`.env` contiene credenciales y queda fuera del webroot. La interfaz llama a `api/...`;
nginx redirige esas peticiones al controlador `api/index.php`.

Para instalacion y actualizacion en produccion, sigue [`DEPLOY.md`](DEPLOY.md).

## Uso

1. Inicia sesion con un usuario de gestion.
2. Desde **Usuarios**, crea, edita, activa o desactiva las cuentas que necesiten introducir datos.
3. Crea las pistas de atletismo.
4. Registra atletas manualmente o importalos desde CSV usando la plantilla descargable.
5. Define las pruebas, o usa **Cargar pruebas habituales**.
6. Introduce marcas desde la pantalla principal.
7. Desde **Marcas registradas**, busca un atleta para revisar, editar o eliminar sus marcas.

La categoria se calcula en la API segun la edad cumplida del atleta en la fecha de la
marca: `sub8`, `sub10`, `sub12`, `sub14`, `sub16`, `sub18`, `sub20`, `sub23`,
`senior` (23 a 34 anos) y `master` (desde 35 anos).

## Importacion de atletas

El CSV puede estar separado por punto y coma o coma y debe incluir las columnas `Nombre`,
`Apellidos` y `Fecha de nacimiento`. Las fechas admitidas son `AAAA-MM-DD` y `DD/MM/AAAA`.

Los datos introducidos en versiones anteriores que usaban `localStorage` no se migran
automaticamente a MySQL.
