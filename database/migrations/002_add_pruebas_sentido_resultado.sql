-- Permite elegir si la mejor marca de una prueba es la menor o la mayor.
ALTER TABLE pruebas
  ADD COLUMN sentido_resultado ENUM('menor', 'mayor') NOT NULL DEFAULT 'menor' AFTER nombre;

-- Los tiempos/carreras mantienen el valor menor por defecto.
UPDATE pruebas
SET sentido_resultado = 'mayor'
WHERE nombre IN (
  'Altura', 'Longitud', 'Triple salto', 'Pertiga', 'Peso', 'Disco', 'Jabalina', 'Martillo', 'Decatlon', 'Heptatlon'
);
