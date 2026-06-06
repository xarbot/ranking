# Modelo de datos

Fuente principal: `database/init.sql`. Para cambios estructurales, revisar tambien la ultima migracion relacionada en `database/migrations/`.

## Tablas principales

- `usuarios`: cuentas del panel. Campos clave: `nombre`, `usuario`, `password_hash`, `activo`, `rol`.
- `atletas`: deportistas. Campos: `nombre`, `apellidos`, `fecha_nacimiento`, `sexo`.
- `pruebas`: catalogo deportivo. Campos: `nombre`, `ambito`, `grupo`, `sentido_resultado`, `informacion_adicional`.
- `ciudades`: catalogo de localidades. Campos: `nombre`, `provincia`.
- `pistas`: catalogo auxiliar de pistas por ciudad y ambito. Conserva compatibilidad con marcas historicas.
- `marcas`: resultados deportivos. Campos: `atleta_id`, `prueba_id`, `ciudad_id`, `nombre_pista`, `fecha`, `resultado`, `caracteristica_tecnica`, `categoria`, auditoria.
- `marcas_borradas`: papelera/auditoria para marcas eliminadas.
- `usuario_atleta_permisos`: permisos de usuarios normales sobre atletas concretos.
- `traducciones`: literales editables en castellano y catalan.
- `schema_migrations`: migraciones SQL ya aplicadas.

## Relaciones

- `marcas.atleta_id` -> `atletas.id`.
- `marcas.prueba_id` -> `pruebas.id`.
- `marcas.ciudad_id` -> `ciudades.id`.
- `marcas.pista_id` -> `pistas.id` si existe dato historico.
- `marcas.creado_por` y `actualizado_por` -> `usuarios.id` con borrado a `NULL`.
- `usuario_atleta_permisos` une `usuarios` y `atletas`.
- `pistas.ciudad_id` -> `ciudades.id`.

## Convenciones deportivas

- Atletas: nombre y apellidos se normalizan capitalizados; `sexo` es `masculino` o `femenino`.
- Pruebas: `ambito` puede ser `pista_cubierta`, `aire_libre` o `ruta`.
- Pruebas: `grupo` organiza catalogo; `sentido_resultado` es `menor` para tiempos y `mayor` para saltos/lanzamientos.
- Pruebas: `informacion_adicional` obliga a registrar caracteristica tecnica.
- Marcas: `resultado` se guarda como texto validado; se convierte a valor comparable al calcular rankings.
- Marcas: `categoria` se calcula desde fecha de nacimiento, fecha de marca y sexo.
- Pistas: para pista cubierta/aire libre puede usarse `nombre_pista`; en ruta normalmente no aplica.
- Ciudades: deben existir en catalogo antes de grabar marcas.

## Categorias y rankings

- Las categorias Sub y Senior se calculan por el ano en que el atleta cumple la edad de cambio.
- Master empieza desde la fecha exacta de 35 anos y avanza en tramos de cinco anos.
- Las categorias se separan por sexo en la consulta publica.
- Ranking: cada atleta aparece con su mejor marca por prueba/categoria segun `sentido_resultado`.

## Notas

- La documentacion antigua menciona competiciones, pero el esquema actual no contiene tabla de competiciones.
- No inventar equivalencias de atletas, pruebas, ciudades o marcas ambiguas.
- No modificar migraciones ya aplicadas si puede crearse una nueva.
