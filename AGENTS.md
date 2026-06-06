# Instrucciones para agentes IA

Este repositorio contiene la aplicacion de ranking del Club Atletic Castellar. `AGENTS.md` es solo el punto de entrada breve para agentes locales o remotos.

## Lectura obligatoria

1. Lee primero `docs/agent-context/PROJECT_INDEX.md`.
2. Despues lee solo los documentos que el indice recomiende para la tarea.
3. Usa busqueda/indexacion del editor, `rg` o el indice del proyecto antes de abrir muchos archivos.
4. No cargues archivos grandes completos si basta con buscar simbolos, rutas o fragmentos concretos.
5. No repitas contexto largo en el chat: cita rutas y resume solo lo necesario.

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

## Fuente compartida

La documentacion neutral para cualquier herramienta vive en `docs/agent-context/`. Los archivos especificos de una herramienta, si existen, deben apuntar a esa fuente y no duplicar reglas.
