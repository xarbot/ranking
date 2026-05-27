# Ranking de Atletismo

Aplicacion de mejores marcas de atletismo con consulta publica en `/`, panel de gestion
autenticado en `/admin/`, persistencia MySQL y clasificacion calculada por edad y sexo.

El idioma inicial es catalan. Tanto la consulta publica como la gestion permiten alternar
castellano y catalan; los literales se pueden anadir, editar o retirar desde **Traducciones**.

## Requisitos

- PHP 8.3 con la extension `pdo_mysql`.
- MySQL 8.0 o compatible.
- nginx con PHP 8.3 y el fragmento de rutas incluido en `deploy/nginx/`.

## Base de datos

Crea las tablas solo en una base de datos nueva ya provisionada para la aplicacion:

```sh
mysql --user=ranking_app --password ranking_atletismo < database/init.sql
```

Las instalaciones existentes se actualizan mediante los SQL incrementales de
`database/migrations/`, ejecutados por `php scripts/migrate.php`. La migracion `003` infiere
un sexo inicial para atletas existentes a partir del nombre; debe revisarse en el panel,
porque la inferencia no puede ser exacta para todos los nombres. La migracion `004` deja
unicamente el catalogo reglado: las marcas asociadas a pruebas antiguas se mantienen y se
reasignan a `Aire Libre / Curses / 100` para revisarlas posteriormente.

El esquema contiene:

- `usuarios`: cuentas de gestion.
- `atletas`: nombre, fecha de nacimiento y sexo (`masculino` o `femenino`).
- `pruebas`: ambito, grupo, prueba, criterio de ranking y si exige caracteristica tecnica.
- `ciudades`: catalogo para el autocompletado de localizacion.
- `marcas`: atleta, prueba, ciudad, nombre opcional de pista, marca, dato tecnico y categoria.
- `traducciones`: traduccion editable de los literales de la aplicacion.
- `pistas`: tabla conservada unicamente para migrar marcas historicas.

## Catalogo y clasificaciones

Al abrir la gestion se crea el catalogo cerrado definido de `Pista Cubierta`, `Aire Libre` y
`Ruta`, con sus grupos y pruebas. El apartado de pruebas es de consulta y no permite crear
pruebas fuera del listado. Tanques y lanzamientos requieren el texto libre de caracteristica
tecnica. En pista cubierta y aire libre se puede indicar ademas el nombre de la pista.

Las clasificaciones separan sexo: `Sub8 - Masculino`, `Sub8 - Femenino`, etc. Desde los
35 anos se generan tramos de cinco anos (`Master 35`, `Master 40`, ... `Master 100`),
tambien separados por sexo. En la consulta publica solo aparecen pruebas y categorias que
tienen marcas registradas.

## Ciudades

El apartado **Pistas** se sustituye por **Ciudades**. `database/ciudades_es.csv` incluye los
8.132 municipios de la relacion del INE a 1 de enero de 2026 (publicada el 4 de febrero de
2026), obtenida del dataset CodeForSpain `ds-organizacion-administrativa`; se carga
automaticamente la primera vez que se abre la gestion. El panel conserva una importacion CSV
(`Ciudad`, `Provincia`) para actualizar el catalogo en el futuro. Las localidades de pistas
historicas se trasladan automaticamente durante la migracion.

## Cargas CSV

La plantilla de atletas descargable incluye `Nombre`, `Apellidos`, `Fecha de nacimiento` y
`Sexo`. Las fechas usan `AAAA-MM-DD` y el sexo es `masculino` o `femenino`. Nombres y
apellidos se almacenan capitalizados; al cargar la aplicacion se normalizan igualmente los
atletas que ya existian.

Antes de importar resultados se selecciona el atleta al que corresponden todas las marcas,
con acceso directo a su alta si aun no existe. La descarga de resultados es una plantilla
`plantilla-resultados.xlsx` distribuible: incorpora desplegables dependientes para `Ambito`,
`Grupo` y `Prueba`, y la lista de ciudades para escoger o buscar `Ciudad`. La persona que la
rellena la devuelve en formato Excel; antes de importarla se guarda la hoja `Resultados` como
CSV. El CSV no incluye el atleta y contiene `Ambito`, `Grupo`, `Prueba`, `Caracteristica
tecnica`, `Marca`, `Fecha`, `Ciudad` y `Pista`. La importacion comprueba prueba, ciudad y
campos obligatorios antes de grabar ninguna marca.

La plantilla se regenera tras modificar el catalogo o las ciudades mediante
`python3 scripts/generate_results_template.py`.

## Despliegue nginx

1. Publica el repositorio en el `root` nginx; `/` sirve los listados y `/admin/` la gestion.
2. Copia `.env.example` como `.env` en el directorio padre de la raiz publica.
3. Edita `.env` con `DB_HOST`, `DB_NAME`, `DB_USER` y `DB_PASS` del servidor.
4. Instala `deploy/nginx/ranking.conf.example`, recarga nginx tras ejecutar `nginx -t` y ejecuta las migraciones.
5. Accede a `/admin/` para crear el primer usuario de gestion y registrar marcas.

`.env` contiene credenciales y queda fuera del webroot. Las interfaces llaman a `api/...`;
nginx redirige esas peticiones al controlador `api/index.php`.

Fuente del catalogo de municipios: <https://github.com/codeforspain/ds-organizacion-administrativa> (datos derivados del INE, consultado en mayo de 2026).
