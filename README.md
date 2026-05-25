# Ranking de Atletismo

Aplicacion de entrada de marcas de atletismo con persistencia MySQL y categoria
calculada automaticamente por edad.

## Requisitos

- PHP 8.3 con la extension `pdo_mysql`.
- MySQL 8.0 o compatible.
- Apache con `mod_rewrite` habilitado para las rutas de `/api`.

## Base de datos

Crea las tablas ejecutando el esquema:

```sh
mysql -u root -p < schema.sql
```

Crea un usuario de aplicacion con acceso solo a esta base:

```sql
CREATE USER 'ranking_app'@'localhost' IDENTIFIED BY 'cambia_esta_clave';
GRANT ALL PRIVILEGES ON ranking_atletismo.* TO 'ranking_app'@'localhost';
FLUSH PRIVILEGES;
```

El esquema contiene:

- `usuarios`: cuentas con contrasena cifrada para acceder a la gestion de datos.
- `atletas`: nombre, apellidos y fecha de nacimiento.
- `pistas`: instalaciones y localidad.
- `pruebas`: catalogo de pruebas.
- `marcas`: atleta, prueba, pista, fecha, resultado y categoria, con claves foraneas.

## Despliegue PHP

1. Sube `admin/`, `schema.sql` y `config.example.php`, conservando `admin/api/.htaccess`.
2. Copia `config.example.php` como `config.php`.
3. Edita `config.php` con el usuario y clave de MySQL del servidor.
4. Asegurate de que Apache permite el archivo `api/.htaccess` (`AllowOverride FileInfo`).
5. Accede a la ruta `/admin/` dentro de la URL donde hayas publicado la aplicacion.

En la primera apertura, la aplicacion solicita crear el primer usuario de gestion.

`config.php` contiene credenciales y esta excluido de Git. La interfaz de `admin/` llama a
`api/...`; `admin/api/.htaccess` redirige esas peticiones a `admin/api/index.php`.

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
