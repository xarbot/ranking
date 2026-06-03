# Decisiones tecnicas

Este archivo registra decisiones importantes para mantener coherencia entre sesiones y agentes.

## Decisiones vigentes

### 2026-06-03: `docs/agent-context/` como fuente neutral compartida

- `AGENTS.md` sera un punto de entrada breve en la raiz del repositorio.
- `docs/agent-context/` sera la fuente neutral de conocimiento compartido para Codex, Zoo Code, Cline, Roo, Aider u otros agentes.
- El contexto comun no dependera de directorios especificos de herramientas como `.clinerules` o `.roo/rules`.
- Si en el futuro se crean adaptadores especificos, deberan derivar de `docs/agent-context/` y no sustituirlo.

### 2026-06-03: Mantener compatibilidad con hosting PHP estandar

- La produccion objetivo solo garantiza PHP y MySQL.
- No se debe proponer Docker para produccion.
- No se deben introducir dependencias complejas sin justificacion y aprobacion.

### 2026-06-03: Cambios pequenos y seguros

- Los agentes deben evitar refactors grandes si la tarea pedida es pequena.
- Antes de tocar archivos deben explicar la intencion.
- Despues de tocar archivos deben resumir cambios, pruebas y riesgos.
- No se deben modificar credenciales ni datos sensibles.

## Pendiente de decidir

- Convencion exacta de nombres de ramas para trabajo con IA.
- Formato definitivo para registrar tareas de revision de datos ambiguos.
- Politica de versionado cuando los cambios sean solo documentacion.
