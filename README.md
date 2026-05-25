# Ranking de Atletismo

Aplicacion web autonoma para registrar marcas de atletismo y clasificarlas automaticamente
por categoria de edad.

## Uso

Abre `index.html` en un navegador. Los datos se almacenan en `localStorage` del navegador,
sin servidor ni instalacion.

1. Crea las pistas de atletismo.
2. Registra atletas con su fecha de nacimiento.
3. Define las pruebas, o usa **Cargar pruebas habituales**.
4. Introduce marcas desde la pantalla principal.

La categoria se calcula segun la edad cumplida del atleta en la fecha de la marca:
`sub8`, `sub10`, `sub12`, `sub14`, `sub16`, `sub18`, `sub20`, `sub23`, `senior`
(23 a 34 anos) y `master` (desde 35 anos).

Al filtrar el ranking por una prueba, los tiempos se ordenan de menor a mayor y las
pruebas de distancia o puntos de mayor a menor. El ranking visible se puede exportar
como CSV.
