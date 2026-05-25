# Despliegue en nginx con PHP 8.3

La aplicacion se publica desde `/var/www/ranking.clubatleticcastellar.cat/htdocs`, el `root` nginx configurado para el dominio.

## Requisitos

- nginx con HTTPS y acceso al directorio de includes del virtual host.
- PHP 8.3 con las extensiones `pdo_mysql` y `session`.
- MySQL 8.0 o compatible.
- Git y acceso SSH al servidor para las actualizaciones.

## Instalacion inicial

1. Instala el codigo en el `root` ya definido en el bloque `server`.

```sh
cd /var/www/ranking.clubatleticcastellar.cat
git clone URL_DEL_REPOSITORIO htdocs
cd htdocs
```

2. Instala el fragmento nginx que enruta la API y bloquea ficheros privados. El bloque `server` facilitado ya incluye ese directorio.

```sh
cp deploy/nginx/ranking.conf.example ../conf/nginx/ranking.conf
nginx -t
systemctl reload nginx
```

3. Crea el entorno fuera de `htdocs` y limita su lectura.

```sh
cp .env.example ../.env
chmod 600 ../.env
```

Edita `/var/www/ranking.clubatleticcastellar.cat/.env` con los datos asignados a la aplicacion:

```dotenv
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=ranking_atletismo
DB_USER=ranking_app
DB_PASS=una_clave_unica_del_servidor
```

La API y el ejecutor de migraciones leen ese `.env`, que queda fuera del directorio publico `htdocs`.

## Configuracion de MySQL

Las siguientes sentencias son para una instalacion nueva, sobre una base aun no usada por la aplicacion:

```sql
CREATE DATABASE ranking_atletismo
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
CREATE USER 'ranking_app'@'localhost' IDENTIFIED BY 'una_clave_unica_del_servidor';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES
  ON ranking_atletismo.* TO 'ranking_app'@'localhost';
FLUSH PRIVILEGES;
```

No ejecutes la inicializacion sobre una base que ya contiene datos de la aplicacion; para instalaciones existentes utiliza las migraciones.

Carga el esquema inicial en la base nueva:

```sh
mysql --user=ranking_app --password ranking_atletismo < database/init.sql
```

El fichero `database/init.sql` crea las tablas iniciales y registra la version de partida en `schema_migrations`.

## Comprobacion

Comprueba que la portada y la API responden, y que un fichero interno queda bloqueado:

```sh
curl -I https://ranking.clubatleticcastellar.cat/
curl -i https://ranking.clubatleticcastellar.cat/api/auth/status
curl -I https://ranking.clubatleticcastellar.cat/lib/env.php
```

La tercera peticion debe responder `403 Forbidden`.

## Permisos
Manten el repositorio bajo un usuario de despliegue y concede al proceso web solamente lectura del codigo y acceso a las rutas que use la aplicacion.

```sh
chown -R deploy:www-data /var/www/ranking.clubatleticcastellar.cat/htdocs
find /var/www/ranking.clubatleticcastellar.cat/htdocs -type d -exec chmod 750 {} \;
find /var/www/ranking.clubatleticcastellar.cat/htdocs -type f -exec chmod 640 {} \;
chmod 600 /var/www/ranking.clubatleticcastellar.cat/.env
```

El fichero `conf/nginx/ranking.conf` impide publicar o ejecutar `database/`, `lib/`, `scripts/`, `storage/`, `vendor/` y otros directorios privados incluidos en `htdocs`.

## Actualizacion segura

Antes de actualizar, genera una copia de seguridad de la base de datos. Despues ejecuta:

```sh
cd /var/www/ranking.clubatleticcastellar.cat/htdocs
git pull --ff-only
if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader; fi
php scripts/migrate.php
```

Actualmente la aplicacion no utiliza Composer ni cache de servidor, por lo que no hay dependencias o cache que limpiar. Si se incorporan en una version futura, se documentara el comando correspondiente en este procedimiento.

Las migraciones de `database/migrations/` se aplican por orden de nombre y solo una vez. Si una migracion falla, conserva el backup y resuelve el error antes de publicar la nueva version.

## Copia de seguridad

Genera una copia antes de cada actualizacion y mantenla en una ruta externa al repositorio:

```sh
mkdir -p /var/backups/ranking
mysqldump --single-transaction --routines --triggers --user=backup_user --password ranking_atletismo > /var/backups/ranking/ranking_$(date +%F_%H%M).sql
```

Guarda los backups con permisos restringidos y aplica la politica de retencion del servidor.

## Restauracion

Restaura primero en una base separada para validar el backup sin afectar a la base activa:

```sh
mysql --user=root --password -e "CREATE DATABASE ranking_atletismo_restore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql --user=restore_user --password ranking_atletismo_restore < /var/backups/ranking/ranking_FECHA.sql
```

Configura una copia de `.env` para la base restaurada, comprueba el acceso y conmuta el servicio solo despues de validar los datos.

## Migraciones futuras

Cada cambio de estructura debe incorporarse como un nuevo archivo SQL en `database/migrations/`, conservando intactos los ficheros ya aplicados. El nombre debe comenzar con un numero creciente, por ejemplo `002_indice_marcas.sql`.

`scripts/migrate.php` lee la configuracion de `.env`, ejecuta las versiones pendientes y registra cada version completada en `schema_migrations`. Realiza siempre un backup antes de aplicar una migracion en produccion.
