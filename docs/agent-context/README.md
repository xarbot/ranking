# Contexto compartido para agentes

Este directorio es la fuente comun y neutral de conocimiento para Codex, Zoo Code, Cline, Roo, Aider u otros agentes locales conectados a modelos externos o a Ollama.

No depende de una herramienta concreta. `AGENTS.md` funciona como entrada breve en la raiz y este directorio contiene el contexto estable que los agentes deben consultar antes de cambios relevantes.

## Indice

- `PROJECT_CONTEXT.md`: descripcion del proyecto, objetivo general y modulos.
- `ARCHITECTURE.md`: arquitectura tecnica conocida, produccion y restricciones.
- `DOMAIN_RULES.md`: reglas del dominio deportivo y catalogos internos.
- `CODING_STANDARDS.md`: reglas de codigo y compatibilidad tecnica.
- `WORKFLOW.md`: flujo obligatorio para agentes antes, durante y despues de editar.
- `DATA_IMPORTS.md`: importacion de resultados, CSV/PDF y validaciones.
- `DECISIONS.md`: decisiones tecnicas aceptadas.
- `TASKS.md`: lista viva de tareas pendientes, en curso y hechas.
- `CHANGELOG_AI.md`: registro de cambios realizados con ayuda de IA.

## Principio de mantenimiento

Si una regla es general para cualquier agente, debe vivir aqui. Los archivos especificos de herramientas solo deben crearse cuando el usuario lo pida expresamente y deben derivar de este contexto, no sustituirlo.
