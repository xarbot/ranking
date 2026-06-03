# Contexto del proyecto para IA

## Resumen

Este repositorio contiene la aplicacion de ranking del Club Atletic Castellar. Permite consultar rankings publicos y gestionar atletas, pruebas, ciudades, marcas, usuarios, permisos, traducciones e importaciones de resultados.

El objetivo tecnico es mantener una aplicacion sencilla, desplegable en hosting PHP estandar, con base de datos MySQL y sin dependencias complejas innecesarias.

## Arquitectura

- `/index.html`, `/app.js`, `/styles.css`: consulta publica del ranking.
- `/admin/index.html`, `/admin/app.js`, `/admin/styles.css`: panel de gestion autenticado.
- `/api/index.php`: controlador principal de API, sesiones, validaciones, consultas y operaciones de gestion.
- `/lib/env.php`: lectura de configuracion de entorno.
- `/database/init.sql`: esquema inicial.
- `/database/migrations/`: migraciones incrementales.
- `/database/ciudades_es.csv`: catalogo inicial de ciudades.
- `/scripts/`: utilidades PHP para migraciones y generacion de plantillas.
- `/assets/`: logos y plantillas Excel para importacion.
- `/deploy/nginx/`: ejemplo de configuracion nginx.

## Produccion

El servidor de produccion solo tiene PHP disponible y usa MySQL. La aplicacion debe seguir siendo compatible con hosting PHP estandar.

No se debe proponer Docker para produccion. Tampoco se deben introducir servicios residentes, workers, colas, builders obligatorios o dependencias complejas sin una justificacion muy clara y aprobacion del usuario.

La configuracion sensible se lee desde `.env` fuera del webroot. No modificar ni exponer credenciales.

## Datos y dominio

El sistema trabaja con:

- atletas;
- pruebas;
- marcas;
- competiciones;
- ciudades;
- rankings;
- importacion de resultados;
- usuarios y permisos;
- traducciones castellano/catalan.

Las marcas de carreras pueden tener formatos como `ss.cc`, `m:ss.cc` o `h:mm:ss`. Los saltos y lanzamientos usan metros con punto decimal, por ejemplo `9.12`.

Las ciudades y pruebas deben coincidir con los catalogos internos. Si una prueba, ciudad o atleta no esta claro, debe marcarse para revision y no inventarse.

## Flujo de colaboracion con IA

Los agentes deben trabajar en ramas y no directamente sobre produccion. Antes de modificar archivos, deben explicar que quieren tocar. Despues de modificar archivos, deben resumir cambios, indicar pruebas realizadas y avisar de riesgos.

El usuario trabaja habitualmente en castellano y catalan. Por defecto, responder en castellano; si el usuario escribe en catalan, responder en catalan.
