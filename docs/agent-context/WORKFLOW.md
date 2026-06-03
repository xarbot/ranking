# Flujo de trabajo para agentes

## Antes de modificar

- Analizar la peticion y revisar el contexto relevante de `docs/agent-context/`.
- Comprobar el estado del working tree.
- Indicar que archivos se quieren tocar y por que.
- Pedir confirmacion antes de cambios grandes, riesgosos, destructivos o que afecten a datos, permisos, migraciones o produccion.

## Durante la modificacion

- Aplicar cambios pequenos y enfocados.
- Respetar cambios existentes del usuario.
- No revertir trabajo ajeno sin permiso.
- No introducir dependencias nuevas sin justificacion y aprobacion.
- No modificar codigo funcional si la tarea es solo documental.

## Despues de modificar

- Mostrar un resumen de cambios.
- Indicar pruebas o comprobaciones realizadas.
- Avisar de riesgos, limitaciones o tareas pendientes.
- Mostrar el diff o resumirlo con rutas concretas cuando el usuario lo pida.
- Actualizar `CHANGELOG_AI.md` cuando el cambio tenga valor como memoria para futuras sesiones.
