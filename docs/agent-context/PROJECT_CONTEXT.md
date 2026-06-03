# Contexto del proyecto

## Resumen

Este repositorio contiene la aplicacion de ranking del Club Atletic Castellar. Su objetivo es publicar rankings deportivos y facilitar la gestion interna de atletas, pruebas, ciudades, competiciones, marcas, usuarios, permisos, traducciones e importaciones de resultados.

La aplicacion debe seguir siendo sencilla, mantenible y compatible con un hosting PHP estandar con MySQL.

## Funcionamiento global

- La parte publica permite consultar rankings y marcas.
- El panel de gestion autenticado permite mantener catalogos y resultados.
- La API centraliza validaciones, sesiones, consultas y operaciones de gestion.
- La persistencia se realiza en MySQL.
- El frontend es estatico y usa HTML, CSS y JavaScript sin framework pesado.

## Modulos principales

- Consulta publica de rankings.
- Gestion de atletas.
- Gestion de pruebas.
- Gestion de ciudades.
- Gestion de competiciones.
- Gestion de marcas.
- Importacion de resultados.
- Gestion de usuarios y permisos.
- Traducciones en castellano y catalan.

## Idioma de trabajo

El usuario trabaja habitualmente en castellano y catalan. Por defecto, los agentes deben responder en castellano. Si el usuario escribe en catalan, deben responder en catalan.
