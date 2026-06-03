# Dominio del ranking

## Entidades principales

- Atletas: nombre, apellidos, fecha de nacimiento y sexo.
- Pruebas: ambito, grupo, nombre, criterio de ranking y requisitos tecnicos.
- Marcas: atleta, prueba, ciudad, fecha, resultado, categoria y datos tecnicos opcionales.
- Ciudades: catalogo interno usado para seleccionar localizaciones.
- Competiciones: contexto deportivo cuando aplique a resultados importados o registrados.
- Rankings: clasificaciones calculadas a partir de mejores marcas.

## Formatos de marcas

- Carreras: aceptar formatos como `ss.cc`, `m:ss.cc` o `h:mm:ss`.
- Tambien pueden aparecer formatos normalizables procedentes de resultados reales.
- Saltos y lanzamientos: metros con punto decimal, por ejemplo `9.12`.
- No confundir tiempos con distancias: el tipo de prueba determina como interpretar la marca.

## Catalogos internos

- Las pruebas deben coincidir con el catalogo interno de `pruebas`.
- Las ciudades deben coincidir con el catalogo interno de `ciudades`.
- Los atletas deben coincidir con los registros existentes o pasar por el flujo de alta/revision.
- Si una prueba, ciudad o atleta no esta claro, marcar para revision y no inventar.

## Importacion de resultados

- Validar todas las filas antes de grabar marcas.
- Si hay errores de catalogo o datos obligatorios, informar filas afectadas.
- No grabar parcialmente una importacion si el flujo espera validacion completa.
- Conservar criterios de categoria por fecha de marca, fecha de nacimiento y sexo.

## Criterios de seguridad de datos

- No borrar marcas, atletas, pruebas, ciudades ni competiciones dudosas sin confirmacion.
- Para duplicados o registros ambiguos, proponer revision o fusion segura.
- Mantener trazabilidad cuando una accion afecte a resultados historicos.
