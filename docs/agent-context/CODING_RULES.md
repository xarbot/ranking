# Reglas de codigo para agentes

## Principios

- Cambiar solo lo necesario para la peticion.
- Preferir cambios pequenos, revisables y reversibles.
- No hacer refactors grandes si la tarea es pequena.
- No modificar codigo funcional en tareas documentales.
- Explicar antes de editar que archivos se tocaran y por que.
- Resumir despues cambios, comprobaciones y riesgos.

## Compatibilidad

- Produccion: PHP + MySQL en hosting estandar.
- No proponer Docker para produccion.
- No introducir Node/builders/dependencias obligatorias sin aprobacion.
- Evitar extensiones PHP no verificadas en produccion.

## PHP y MySQL

- Usar PDO y consultas preparadas.
- Validar entradas antes de escribir.
- Mantener transacciones y respuestas JSON coherentes en `api/index.php`.
- Crear migraciones incrementales para cambios de esquema.
- No modificar migraciones ya aplicadas si puede agregarse una nueva.
- Mantener mensajes claros para usuarios de gestion.

## Frontend

- Seguir patrones actuales de HTML/CSS/JS sin framework.
- Mantener textos visibles en castellano y catalan cuando aplique.
- Revisar cache busters `?v=` si cambia un asset servido al navegador.
- Evitar reestructurar pantallas completas para cambios puntuales.

## Datos sensibles

- No tocar `.env`, credenciales, backups, dumps ni secretos.
- No mostrar datos sensibles en respuestas, logs o documentacion.
- No cambiar configuracion de produccion sin peticion explicita.

## Validacion

- Para PHP tocado: `php -l ruta`.
- Para docs: revisar rutas y `git diff --check`.
- Para SQL: revisar migracion, orden y compatibilidad MySQL.
- Para importaciones/rankings: validar casos de error y no solo el caso feliz.

## Version

- Si el cambio es publicable y afecta al comportamiento o UI desplegada, subir +0.1 donde corresponda.
- Mantener `README.md`, version visible y cache busters coherentes cuando aplique.
- Cambios solo documentales no requieren version salvo que el usuario lo pida.
