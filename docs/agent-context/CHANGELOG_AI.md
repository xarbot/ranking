# Changelog de cambios con IA

Registro de cambios realizados con ayuda de agentes IA. No sustituye al historial de Git; sirve como memoria legible para futuras sesiones.

## 2026-06-03

### Aniadido

- Creado `docs/agent-context/` como directorio neutral de conocimiento compartido para agentes.
- Creado `README.md` como indice del contexto compartido.
- Creados documentos de contexto, arquitectura, dominio, estandares, flujo, importaciones, decisiones, tareas y changelog.
- Creada o actualizada `.rooignore` con exclusiones para indexacion de agentes.

### Cambiado

- `AGENTS.md` se reduce a un punto de entrada breve que remite a `docs/agent-context/`.
- El conocimiento util de `.clinerules` se migra al contexto neutral.

### Eliminado

- Eliminado `.clinerules` porque duplicaba informacion ya migrada y dependia de una herramienta concreta.
- Eliminado `.roo/rules` porque no se deben crear adaptadores especificos por ahora.

### Notas

- No se ha modificado codigo funcional.
- No se han tocado credenciales, base de datos ni configuracion de produccion.
