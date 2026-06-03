# Contexto del proyecto

Este proyecto es la aplicacion de ranking del Club Atletic Castellar.

## Arquitectura general

- Consulta publica servida desde `/`.
- Panel de gestion autenticado servido desde `/admin/`.
- API implementada en PHP, principalmente en `api/index.php`.
- Base de datos MySQL.
- Frontend con HTML, CSS y JavaScript sin framework pesado.
- Migraciones SQL en `database/migrations/`.
- Scripts auxiliares PHP en `scripts/`.
- Configuracion sensible fuera del webroot mediante `.env`.

## Entorno de produccion

- El servidor de produccion solo tiene PHP disponible.
- MySQL es la base de datos soportada.
- El despliegue objetivo es hosting PHP estandar con nginx/PHP.
- No proponer Docker para produccion.
- No asumir Node, builders, colas, servicios externos ni procesos residentes en produccion salvo confirmacion explicita.

## Alcance funcional

La aplicacion gestiona:

- atletas;
- pruebas;
- marcas;
- competiciones;
- ciudades;
- rankings;
- importacion de resultados;
- traducciones en castellano y catalan;
- usuarios y permisos de gestion.

## Idioma

- Responder por defecto en castellano.
- Si el usuario escribe en catalan, responder en catalan.
- Mantener nombres tecnicos en ingles cuando sean propios del codigo o de herramientas externas.
