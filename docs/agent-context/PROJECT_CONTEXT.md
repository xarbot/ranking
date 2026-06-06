# Contexto del proyecto

## Resumen

Aplicacion de rankings de atletismo del Club Atletic Castellar. Publica mejores marcas y rankings por prueba, categoria y sexo, y ofrece un panel autenticado para mantener datos deportivos.

El objetivo tecnico es conservar una aplicacion sencilla, revisable y desplegable en hosting PHP estandar con MySQL.

## Que problema resuelve

- Centraliza atletas, pruebas, ciudades/pistas, marcas, usuarios, permisos y traducciones.
- Permite importar resultados desde plantillas/CSV con validaciones antes de grabar.
- Calcula categorias deportivas a partir de fecha de nacimiento, fecha de marca y sexo.
- Muestra rankings publicos separando pruebas, categorias y criterios de ordenacion.

## Tecnologias

- PHP 8.3 compatible con hosting estandar.
- MySQL 8.0 o compatible.
- PDO con consultas preparadas.
- HTML, CSS y JavaScript sin framework pesado.
- Scripts PHP para migraciones y generacion de plantillas.

## Produccion

- Produccion solo garantiza PHP y MySQL.
- No asumir Docker, Node, builders, workers, colas ni servicios externos.
- `.env` vive fuera del webroot en despliegue y contiene credenciales.
- nginx sirve `/`, `/admin/` y enruta `/api/` al controlador PHP.

## Restricciones criticas

- No modificar credenciales, `.env`, backups, dumps ni datos sensibles.
- No cambiar configuracion de produccion sin peticion explicita.
- No tocar codigo funcional cuando la tarea sea documental.
- Evitar refactors grandes; priorizar cambios pequenos y reversibles.
- Mantener interfaz y literales en castellano/catalan cuando afecte a textos visibles.
- Responder por defecto en castellano; si el usuario escribe en catalan, responder en catalan.
