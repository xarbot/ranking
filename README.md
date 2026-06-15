# Ranking de Atletismo

Aplicacion de mejores marcas de atletismo con consulta publica en `/`, panel de gestion
autenticado en `/admin/`, persistencia MySQL y clasificacion calculada por edad y sexo.

El idioma inicial es catalan. Tanto la consulta publica como la gestion permiten alternar
castellano y catalan; los literales se pueden anadir, editar o retirar desde **Traducciones**.

## Agentes IA

Los agentes deben empezar por `AGENTS.md` y leer despues `docs/agent-context/PROJECT_INDEX.md`.
Ese indice indica que documentos abrir segun la tarea para evitar cargar contexto innecesario.

## Version

- Version en produccion: `3.8`
- Version del repositorio: `3.8`

El pie de las paginas muestra la version desplegada. A partir de `0.1`, cada peticion
que genere cambios publicables debe incrementar la version en `0.1` y actualizar este
apartado y los pies.

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

La instalacion incluye un catalogo inicial de `Pista Cubierta`, `Aire Libre` y `Ruta`, con
sus grupos y pruebas. Desde el apartado de pruebas se pueden anadir, editar y eliminar
pruebas. Tanques y lanzamientos requieren el texto libre de caracteristica tecnica. En pista
cubierta y aire libre se puede indicar ademas el nombre de la pista.

Las clasificaciones separan sexo: `Sub8 - Masculino`, `Sub8 - Femenino`, etc. Desde los
grupos Sub hasta Senior se asignan por el ano en que el atleta cumple la edad de cambio.
Desde la fecha exacta en que cumple 35 anos se generan tramos de cinco anos (`Master 35`, `Master 40`, ... `Master 100`),
tambien separados por sexo. En la consulta publica solo aparecen ambitos, grupos, pruebas y
categorias que tienen marcas registradas; las pruebas se filtran de forma jerarquica por
ambito y grupo. Las ultimas marcas muestran, cuando procede, caracteristica tecnica y nombre
de pista bajo la prueba y la ciudad.

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

La carga masiva de resultados dispone de un flujo para un unico atleta seleccionado en la web,
con acceso directo a su alta si aun no existe, y de otro flujo multiatleta cuyo fichero
incorpora `Atleta` como primera columna, con el valor
`Nombre Apellidos`. Este segundo flujo revisa primero los atletas existentes y permite dar de
alta los que faltan indicando nombre, apellidos, fecha de nacimiento y sexo; si no se completa
un alta, se omiten sus marcas.

Cada flujo permite descargar una plantilla Excel (`.xlsx`), compatible tambien con
LibreOffice/OpenOffice. Ofrece `Ambito / Grupo` en una unica columna y un desplegable
`Prueba` filtrado por la opcion anterior, ademas de las hojas auxiliares `Pruebas` y
`Ciudades`. La persona que la rellena la devuelve en formato Excel; antes de importarla se
guarda la hoja `Resultados` como CSV. El CSV individual contiene `Ambito / Grupo`, `Prueba`,
`Caracteristica tecnica`, `Marca`, `Fecha`, `Ciudad` y `Pista`; el CSV multiatleta antepone
`Atleta`. Se siguen admitiendo CSV anteriores con `Ambito` y `Grupo` separados. La fecha de
las marcas puede escribirse como `AAAA-MM-DD`, `AAAA/MM/DD`, `DD-MM-AAAA`, `DD/MM/AAAA`
o con dia, mes y ano abreviados, como `1/7/94` (`1994-07-01`).
La importacion comprueba prueba, ciudad y campos obligatorios antes de grabar ninguna marca.

Las plantillas se regeneran tras modificar el catalogo o las ciudades mediante
`php scripts/generate_results_template.php`.

## Despliegue nginx

1. Publica el repositorio en el `root` nginx; `/` sirve los listados y `/admin/` la gestion.
2. Copia `.env.example` como `.env` en el directorio padre de la raiz publica.
3. Edita `.env` con `DB_HOST`, `DB_NAME`, `DB_USER` y `DB_PASS` del servidor.
4. Instala `deploy/nginx/ranking.conf.example`, recarga nginx tras ejecutar `nginx -t` y ejecuta las migraciones.
5. Accede a `/admin/` para crear el primer usuario de gestion y registrar marcas.

`.env` contiene credenciales y queda fuera del webroot. Las interfaces llaman a `api/...`;
nginx redirige esas peticiones al controlador `api/index.php`.

Fuente del catalogo de municipios: <https://github.com/codeforspain/ds-organizacion-administrativa> (datos derivados del INE, consultado en mayo de 2026).
