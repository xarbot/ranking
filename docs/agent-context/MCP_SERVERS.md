# MCP servers del proyecto ranking

Este proyecto puede trabajarse desde Zed, Codex u otros agentes con ayuda de varios MCP servers.

Este documento resume que servidor usar segun la tarea, que alcance tiene cada uno y que precauciones deben seguir los agentes.

## ranking-filesystem

Servidor MCP de acceso al sistema de archivos del repositorio.

Uso previsto:

- Leer archivos del repositorio.
- Buscar archivos y contenido.
- Editar documentacion o codigo cuando el usuario lo pida explicitamente.
- Revisar estructura del proyecto.
- Crear, mover o eliminar archivos solo cuando la tarea lo requiera.

Ámbito permitido:

- `/data/repos/ranking`

Precauciones:

- No modificar `.env`, credenciales, dumps, backups ni datos sensibles.
- Evitar cambios grandes si la tarea es pequeña.

## ranking-tools

Servidor MCP propio del proyecto ranking con utilidades especificas del dominio.

Ruta:

- `/data/mcp/ranking-tools/server.py`

Variable de entorno:

- `RANKING_REPO=/data/repos/ranking`

Uso previsto:

- Ejecutar herramientas especificas del proyecto.
- Hacer comprobaciones sobre rankings, atletas, duplicados o importaciones.
- Localizar endpoints, funciones PHP o referencias de datos con ayudas especializadas.
- Centralizar utilidades propias que no son genericas del filesystem.

## ranking-git

Servidor MCP para operaciones Git sobre el repositorio.

Uso previsto:

- Consultar `git status`.
- Revisar diffs.
- Consultar historial.
- Comprobar ramas, tags o estado del arbol de trabajo.
- Preparar commits si el usuario lo pide.

Reglas:

- No hacer commit sin permiso explícito.
- No hacer push sin permiso explícito.
- No hacer reset, rebase, checkout destructivo ni limpieza de cambios sin permiso explícito.
- Antes de editar archivos, revisar el estado de Git.
- Después de editar, mostrar resumen y diff.

## ranking-qdrant

Servidor MCP conectado al indice vectorial Qdrant del proyecto.

Configuración actual:

- `QDRANT_URL=http://localhost:6333`
- `COLLECTION_NAME=ranking`
- `QDRANT_READ_ONLY=true`

Uso previsto:

- Buscar contexto historico o semantico del proyecto.
- Consultar decisiones previas.
- Buscar temas como duplicados, importaciones, atletas, estructura de datos o ranking historico.
- Recuperar contexto cuando no sabes aun en que archivos buscar.
- Complementar la lectura directa de archivos.

Importante:

- Está en modo solo lectura.
- No sustituye la lectura de archivos actuales del repo.
- Si los resultados no son buenos, puede deberse a diferencias entre el modelo de embeddings usado para indexar y el modelo usado por el MCP oficial de Qdrant.

## Cuando usar cada servidor

- Usa `ranking-filesystem` para leer, buscar y editar archivos reales del repositorio.
- Usa `ranking-tools` para consultas especificas del proyecto, comprobaciones seguras y ayudas sobre endpoints, tablas o funciones.
- Usa `ranking-git` para revisar estado, diffs e historial, y solo para acciones de escritura en Git si el usuario lo autoriza.
- Usa `ranking-qdrant` para recuperar contexto semantico o historico y orientar la busqueda en el repo.

## Flujo recomendado para agentes

Antes de responder sobre el proyecto:

1. Leer `AGENTS.md`.
2. Leer `docs/agent-context/PROJECT_INDEX.md` si existe.
3. Leer `docs/agent-context/PROJECT_SUMMARY.md` si existe.
4. Leer `docs/agent-context/MCP_SERVERS.md`.
5. Usar `ranking-filesystem` para localizar archivos reales.
6. Usar `ranking-qdrant` para recuperar contexto histórico o semántico.
7. Usar `ranking-git` para comprobar estado y diff cuando haya edición.
8. Usar `ranking-tools` para comprobaciones propias del proyecto.

No inventar archivos, rutas ni funcionalidades. Si algo no se encuentra, indicarlo claramente.
