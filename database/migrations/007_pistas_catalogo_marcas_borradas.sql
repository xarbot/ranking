ALTER TABLE pistas
  ADD COLUMN ciudad_id BIGINT UNSIGNED NULL AFTER id,
  ADD COLUMN ambito ENUM('pista_cubierta', 'aire_libre', 'ruta') NOT NULL DEFAULT 'aire_libre' AFTER ciudad_id,
  ADD KEY idx_pistas_ciudad_ambito (ciudad_id, ambito),
  ADD CONSTRAINT fk_pistas_ciudad FOREIGN KEY (ciudad_id) REFERENCES ciudades (id);

ALTER TABLE pistas
  DROP KEY uq_pistas_nombre_localidad,
  ADD UNIQUE KEY uq_pistas_ambito_ciudad_nombre (ambito, ciudad_id, nombre);

UPDATE pistas p
LEFT JOIN ciudades c ON c.nombre = p.localidad
SET p.ciudad_id = c.id
WHERE p.ciudad_id IS NULL;

INSERT IGNORE INTO pistas (ciudad_id, ambito, nombre, localidad)
SELECT DISTINCT m.ciudad_id, p.ambito, m.nombre_pista, c.nombre
FROM marcas m
JOIN pruebas p ON p.id = m.prueba_id
JOIN ciudades c ON c.id = m.ciudad_id
WHERE m.nombre_pista IS NOT NULL AND m.nombre_pista <> '';

CREATE TABLE IF NOT EXISTS marcas_borradas (
  id BIGINT UNSIGNED NOT NULL,
  atleta_id BIGINT UNSIGNED NOT NULL,
  prueba_id BIGINT UNSIGNED NOT NULL,
  pista_id BIGINT UNSIGNED NULL,
  ciudad_id BIGINT UNSIGNED NOT NULL,
  nombre_pista VARCHAR(160) NULL,
  fecha DATE NOT NULL,
  resultado VARCHAR(40) NOT NULL,
  caracteristica_tecnica VARCHAR(255) NULL,
  categoria VARCHAR(40) NOT NULL,
  creado_por BIGINT UNSIGNED NULL,
  actualizado_por BIGINT UNSIGNED NULL,
  creado_en TIMESTAMP NULL,
  actualizado_en TIMESTAMP NULL,
  borrado_por BIGINT UNSIGNED NULL,
  borrado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_marcas_borradas_borrado_en (borrado_en),
  KEY idx_marcas_borradas_atleta (atleta_id),
  CONSTRAINT fk_marcas_borradas_borrado_por FOREIGN KEY (borrado_por) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB;
