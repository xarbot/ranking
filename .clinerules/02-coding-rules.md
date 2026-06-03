# Reglas de codigo

## Principios

- Mantener compatibilidad con hosting PHP estandar.
- Preferir PHP, MySQL, HTML, CSS y JavaScript ya existentes en el repositorio.
- No introducir dependencias complejas sin justificar necesidad, coste y alternativa simple.
- No hacer refactors amplios cuando la tarea sea pequena.
- Cambiar solo lo necesario para resolver la peticion.
- Mantener los cambios pequenos, revisables y seguros.

## PHP y MySQL

- Usar PDO y consultas preparadas para acceso a base de datos.
- Mantener las migraciones incrementales en `database/migrations/`.
- No modificar migraciones ya aplicadas si puede crearse una nueva migracion.
- Validar entradas antes de escribir en base de datos.
- Conservar mensajes de error claros para usuarios de gestion.

## Frontend

- Seguir los patrones actuales de `index.html`, `app.js`, `styles.css` y equivalentes en `admin/`.
- Evitar frameworks o procesos de build salvo que el usuario los apruebe explicitamente.
- Mantener la interfaz bilingue castellano/catalan cuando afecte a textos visibles.

## Datos sensibles

- No modificar credenciales.
- No crear commits con `.env`, dumps, backups ni secretos.
- No mostrar datos sensibles en respuestas, logs o documentacion.

## Cambios de datos

- No borrar registros dudosos sin confirmacion.
- Si un dato no esta claro, marcarlo para revision.
- No inventar atletas, ciudades, pruebas, marcas ni competiciones.
