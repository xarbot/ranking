# AGENTS.md — Instrucciones para agentes IA

Este repositorio corresponde al proyecto `ranking`.

## Idioma

- Responder en castellano por defecto.
- Si el usuario escribe en catalán, responder en catalán.

## Entorno técnico

- Producción usa PHP.
- La base de datos es MySQL.
- No proponer Docker para producción salvo petición explícita.
- El servidor de producción puede tener limitaciones: asumir PHP/MySQL estándar.

## Seguridad

No modificar nunca sin permiso explícito:

- `.env`
- credenciales
- backups
- dumps de base de datos
- archivos con datos sensibles
- configuración de producción

## Forma de trabajar

- Preferir cambios pequeños, seguros y revisables.
- No hacer refactorings grandes si la tarea es pequeña.
- Antes de modificar código, revisar el estado del repositorio.
- Después de modificar, mostrar resumen y diff.
- No hacer commit sin permiso explícito.
- No hacer push sin permiso explícito.

## Documentación de contexto

Antes de trabajar en el proyecto, revisar si existen:

- `docs/agent-context/PROJECT_INDEX.md`
- `docs/agent-context/PROJECT_SUMMARY.md`
- `docs/agent-context/MCP_SERVERS.md`

Estos archivos contienen contexto para agentes locales/remotos.

## MCP servers disponibles

Este proyecto puede disponer de los siguientes MCP servers:

- `ranking-filesystem`: lectura, búsqueda y edición de archivos del repositorio.
- `ranking-tools`: herramientas propias del proyecto ranking.
- `ranking-git`: estado Git, diffs, historial y ramas.
- `ranking-qdrant`: búsqueda semántica/contexto histórico en Qdrant.

Si el agente tiene acceso a estas herramientas, debe usarlas en lugar de inventar información.

## Flujo recomendado para tareas

1. Leer este archivo.
2. Leer documentación en `docs/agent-context/` si existe.
3. Usar búsquedas dirigidas para localizar archivos reales.
4. Usar `ranking-qdrant` para contexto histórico o semántico.
5. Usar `ranking-git` para revisar estado y diffs.
6. Proponer cambios pequeños.
7. Editar solo los archivos necesarios.
8. Mostrar resumen, diff y pruebas realizadas.
