# Reglas de dominio

## Entidades principales

- Atletas: nombre, apellidos, fecha de nacimiento, sexo y datos necesarios para categorizacion.
- Pruebas: ambito, grupo, nombre, criterio de ranking y requisitos tecnicos.
- Marcas: atleta, prueba, ciudad, fecha, resultado, categoria y datos tecnicos opcionales.
- Competiciones: contexto deportivo de resultados importados o registrados.
- Ciudades: catalogo interno usado para seleccionar localizaciones.
- Rankings: clasificaciones calculadas a partir de mejores marcas y criterios vigentes.

## Formatos de marcas

- Carreras: aceptar formatos como `ss.cc`, `m:ss.cc` o `h:mm:ss`.
- Saltos y lanzamientos: usar metros con punto decimal, por ejemplo `9.12`.
- El tipo de prueba determina como interpretar la marca; no confundir tiempos con distancias.
- Si una marca no puede interpretarse con seguridad, marcarla para revision.

## Catalogos internos

- Las pruebas deben coincidir con el catalogo interno de pruebas.
- Las ciudades deben coincidir con el catalogo interno de ciudades.
- Los atletas deben coincidir con registros existentes o pasar por el flujo de alta/revision.
- Las competiciones deben conservarse de forma coherente cuando aporten contexto al resultado.
- Si una prueba, ciudad, competicion o atleta no esta claro, marcar para revision y no inventar.

## Seguridad de datos deportivos

- No borrar marcas, atletas, pruebas, ciudades ni competiciones dudosas sin confirmacion.
- Para duplicados o registros ambiguos, proponer revision o fusion segura.
- Mantener trazabilidad cuando una accion afecte a resultados historicos.
- No inventar datos para completar registros incompletos.
