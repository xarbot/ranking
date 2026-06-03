# Instrucciones para agentes IA

Este repositorio contiene la aplicacion de ranking del Club Atletic Castellar. Estas reglas son el contexto comun para Codex, Cline y cualquier otro agente que trabaje sobre el proyecto.

## Idioma de trabajo

- Responde por defecto en castellano.
- Si el usuario escribe en catalan, responde en catalan.
- Planes, resumenes, explicaciones y avisos de riesgo deben estar en castellano salvo indicacion contraria.
- Los nombres tecnicos de funciones, clases, variables, comandos, rutas y errores pueden mantenerse en ingles si forman parte del codigo o de herramientas externas.

## Contexto del proyecto

- Aplicacion web de ranking de atletismo del Club Atletic Castellar.
- Consulta publica en `/` y panel de gestion autenticado en `/admin/`.
- API PHP centralizada en `api/index.php`.
- Persistencia en MySQL.
- Frontend estatico con HTML, CSS y JavaScript.
- Migraciones SQL en `database/migrations/`.
- Scripts auxiliares PHP en `scripts/`.

## Restricciones tecnicas

- Produccion solo tiene PHP disponible.
- La base de datos es MySQL.
- Mantener compatibilidad con hosting PHP estandar.
- No proponer Docker para produccion.
- No introducir dependencias complejas sin una justificacion clara.
- Preferir cambios pequenos, revisables y seguros.
- No hacer refactors grandes si la tarea pedida es pequena.

## Dominio funcional

El proyecto gestiona atletas, pruebas, marcas, competiciones, ciudades, rankings e importacion de resultados.

- Las marcas de carreras pueden tener formatos como `ss.cc`, `m:ss.cc` o `h:mm:ss`.
- Los saltos y lanzamientos usan metros con punto decimal, por ejemplo `9.12`.
- Las ciudades y pruebas deben coincidir con los catalogos internos.
- Si una prueba, ciudad o atleta no esta claro, debe marcarse para revision y no inventarse.
- No borrar registros dudosos sin confirmacion explicita.

## Seguridad y datos

- No modificar credenciales ni datos sensibles.
- No exponer contenidos de `.env`, backups, dumps ni datos privados.
- No ejecutar acciones destructivas sobre datos reales sin confirmacion.
- Antes de cambios de base de datos en produccion debe existir backup.

## Flujo de trabajo

- Trabajar siempre en ramas, nunca directamente sobre produccion.
- Antes de modificar archivos, explicar brevemente que se quiere tocar y por que.
- Despues de modificar archivos, resumir cambios, indicar pruebas realizadas y avisar de riesgos.
- Mantener el diff pequeno y facil de revisar.
- Consultar `docs/ai/PROJECT_CONTEXT.md`, `docs/ai/DECISIONS.md`, `docs/ai/TASKS.md` y `docs/ai/CHANGELOG_AI.md` antes de cambios relevantes.

## Documentacion compartida

- `.clinerules/`: reglas de workspace para Cline, separadas por tema.
- `docs/ai/PROJECT_CONTEXT.md`: contexto del proyecto y arquitectura.
- `docs/ai/DECISIONS.md`: decisiones tecnicas aceptadas.
- `docs/ai/TASKS.md`: lista viva de tareas pendientes.
- `docs/ai/CHANGELOG_AI.md`: registro de cambios hechos con ayuda de IA.
