# Decisiones tecnicas

Nota de compatibilidad: `docs/ai/` es historico. La fuente actual esta en `docs/agent-context/`; empezar por `docs/agent-context/PROJECT_INDEX.md`.

Este archivo conserva decisiones anteriores para no romper referencias antiguas.

## Decisiones vigentes

### 2026-06-03: Contexto compartido versionado para agentes IA

- Se crea `AGENTS.md` como resumen principal para cualquier agente.
- Se crea `.clinerules/` con reglas separadas por tema para Cline.
- Se crea `docs/ai/` como memoria versionada del proyecto.
- El idioma por defecto de los agentes sera castellano; si el usuario escribe en catalan, responderan en catalan.

### 2026-06-03: Mantener compatibilidad con hosting PHP estandar

- La produccion objetivo solo garantiza PHP y MySQL.
- No se debe proponer Docker para produccion.
- No se deben introducir dependencias complejas sin justificacion y aprobacion.
- Las soluciones deben encajar con el despliegue documentado en `DEPLOY.md`.

### 2026-06-03: Cambios pequenos y seguros

- Los agentes deben evitar refactors grandes si la tarea pedida es pequena.
- Antes de tocar archivos deben explicar la intencion.
- Despues de tocar archivos deben resumir cambios, pruebas y riesgos.
- No se deben modificar credenciales ni datos sensibles.

## Pendiente de decidir

- Convencion exacta de nombres de ramas para trabajo con IA.
- Formato definitivo para registrar tareas de revision de datos ambiguos.
- Politica de versionado cuando los cambios sean solo documentacion.
