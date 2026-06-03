# Flujo de trabajo

## Antes de editar

- Trabajar siempre en ramas, nunca directamente sobre produccion.
- Revisar el contexto compartido en `AGENTS.md` y `docs/ai/`.
- Explicar brevemente que archivos se quieren tocar y por que.
- Confirmar riesgos si la tarea afecta a datos, migraciones, permisos o despliegue.

## Durante la edicion

- Mantener cambios pequenos y enfocados.
- Respetar cambios no relacionados que ya existan en el working tree.
- No revertir trabajo del usuario sin permiso.
- No introducir dependencias ni herramientas nuevas sin justificacion.
- Preferir soluciones compatibles con PHP/MySQL y hosting estandar.

## Despues de editar

- Resumir los cambios realizados.
- Indicar pruebas o comprobaciones ejecutadas.
- Avisar de riesgos, limitaciones o tareas pendientes.
- Mostrar el diff o explicar donde verlo cuando el usuario lo pida.
- Actualizar `docs/ai/CHANGELOG_AI.md` si el cambio se hizo con ayuda de IA y tiene relevancia para futuras sesiones.

## Produccion

- Nunca trabajar directamente sobre produccion.
- Antes de migraciones o cambios de datos en produccion, exigir backup.
- No proponer Docker para produccion.
- No ejecutar acciones destructivas sin confirmacion explicita.
