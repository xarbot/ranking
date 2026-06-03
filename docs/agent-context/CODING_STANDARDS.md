# Estandares de codigo

## Principios

- Mantener compatibilidad con hosting PHP estandar.
- Preferir PHP, MySQL, HTML, CSS y JavaScript ya existentes en el repositorio.
- Evitar dependencias innecesarias.
- No introducir dependencias complejas sin justificar necesidad, coste y alternativa simple.
- No hacer grandes refactors si la tarea es pequena.
- Cambiar solo lo necesario para resolver la peticion.
- Mantener cambios simples, seguros y revisables.

## PHP y MySQL

- Usar PDO y consultas preparadas para acceso a base de datos.
- Validar entradas antes de escribir en base de datos.
- Mantener migraciones incrementales en `database/migrations/`.
- No modificar migraciones ya aplicadas si puede crearse una nueva migracion.
- Conservar mensajes de error claros para usuarios de gestion.
- Evitar asumir extensiones PHP no verificadas en produccion.

## Frontend

- Seguir los patrones actuales de `index.html`, `app.js`, `styles.css` y equivalentes en `admin/`.
- Evitar frameworks o procesos de build salvo aprobacion explicita.
- Mantener la interfaz bilingue castellano/catalan cuando afecte a textos visibles.

## Datos sensibles

- No modificar credenciales.
- No crear commits con `.env`, dumps, backups ni secretos.
- No mostrar datos sensibles en respuestas, logs o documentacion.
