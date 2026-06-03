# Importacion de datos

## Alcance

La aplicacion puede importar resultados desde fuentes externas, incluyendo CSV y PDF cuando el flujo lo soporte. Las importaciones deben priorizar validacion, trazabilidad y revision de datos ambiguos.

## Validaciones

- Validar todas las filas antes de grabar marcas.
- Comprobar que pruebas, ciudades y atletas coinciden con catalogos o registros internos.
- Comprobar que las marcas tienen formato compatible con el tipo de prueba.
- Informar filas afectadas cuando haya errores de catalogo, formato o datos obligatorios.
- No asumir equivalencias de nombres sin comprobacion.

## Reglas de marcas

- Carreras: formatos como `ss.cc`, `m:ss.cc` o `h:mm:ss`.
- Saltos y lanzamientos: metros con punto decimal, por ejemplo `9.12`.
- No confundir tiempos con distancias.

## Registros dudosos

- Si una prueba, ciudad, competicion, atleta o marca no esta clara, marcar para revision y no inventar.
- No borrar registros dudosos sin confirmacion explicita.
- No grabar parcialmente una importacion si el flujo espera validacion completa.
- Conservar criterios de categoria por fecha de marca, fecha de nacimiento y sexo.
