# Contexto compartido para agentes

Fuente neutral para Codex, Zoo Code, Cline, Roo, Aider u otros agentes. No depende de una herramienta concreta.

## Entrada obligatoria

- `PROJECT_INDEX.md`: indice compacto. Leer primero y despues abrir solo lo necesario.

## Documentos actuales

- `PROJECT_CONTEXT.md`: resumen, problema, tecnologias, produccion y restricciones.
- `ARCHITECTURE.md`: mapa de carpetas, modulos y archivos principales.
- `DATA_MODEL.md`: tablas, campos, relaciones y convenciones deportivas.
- `WORKFLOWS.md`: importaciones, duplicados, rankings, admin, version y validacion.
- `CODING_RULES.md`: reglas para tocar codigo y validar cambios.
- `KNOWN_ISSUES.md`: riesgos, contradicciones y zonas delicadas.
- `CHANGELOG_AGENT.md`: memoria breve de cambios para agentes.

## Referencias historicas o auxiliares

- `DOMAIN_RULES.md`, `DATA_IMPORTS.md`, `DECISIONS.md`, `TASKS.md`: contexto adicional historico si una tarea lo requiere.
- `WORKFLOW.md`, `CODING_STANDARDS.md`, `CHANGELOG_AI.md`: compatibilidad con nombres antiguos; la fuente actual son los documentos nuevos.

## Principio de mantenimiento

No duplicar contexto. Si una regla es general para cualquier agente, debe vivir aqui. Los archivos especificos de herramientas solo deben apuntar a este directorio.
