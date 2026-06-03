# Instrucciones para agentes IA

Este repositorio contiene la aplicacion de ranking del Club Atletic Castellar. `AGENTS.md` es el punto de entrada breve para cualquier agente local o remoto.

Antes de trabajar, lee el contexto compartido neutral en `docs/agent-context/`.

## Reglas criticas

- Responder por defecto en castellano.
- Si el usuario escribe en catalan, responder en catalan.
- Produccion solo garantiza PHP; mantener compatibilidad con hosting PHP estandar.
- La base de datos es MySQL.
- No proponer Docker para produccion.
- No modificar credenciales, `.env`, backups, dumps ni datos sensibles.
- No cambiar configuracion de produccion salvo peticion explicita.
- Preferir cambios pequenos, revisables y seguros.
- No hacer refactors grandes si la tarea pedida es pequena.
- Antes de modificar archivos, explicar brevemente que se quiere tocar y por que.
- Despues de modificar archivos, resumir cambios, indicar pruebas realizadas y avisar de riesgos.

## Contexto compartido

- `docs/agent-context/README.md`: indice del contexto comun.
- `docs/agent-context/PROJECT_CONTEXT.md`: objetivo funcional y modulos.
- `docs/agent-context/ARCHITECTURE.md`: arquitectura tecnica y produccion.
- `docs/agent-context/DOMAIN_RULES.md`: reglas deportivas y catalogos.
- `docs/agent-context/CODING_STANDARDS.md`: estandares de codigo.
- `docs/agent-context/WORKFLOW.md`: flujo obligatorio de trabajo.
- `docs/agent-context/DATA_IMPORTS.md`: importaciones y validaciones.
- `docs/agent-context/DECISIONS.md`: decisiones tecnicas.
- `docs/agent-context/TASKS.md`: lista viva de tareas.
- `docs/agent-context/CHANGELOG_AI.md`: cambios realizados con ayuda de IA.
