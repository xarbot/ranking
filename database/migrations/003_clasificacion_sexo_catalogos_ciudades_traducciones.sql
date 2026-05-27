ALTER TABLE atletas ADD COLUMN sexo ENUM('masculino', 'femenino') NULL AFTER fecha_nacimiento;
UPDATE atletas SET sexo = CASE
  WHEN LOWER(SUBSTRING_INDEX(nombre, ' ', 1)) REGEXP '^(ana|aina|alba|alicia|andrea|anna|beatriz|berta|carla|carme|carmen|celia|clara|claudia|cristina|elena|emma|eva|gemma|ines|irene|isabel|julia|laia|lara|laura|lucia|maria|marta|mireia|natalia|neus|noa|nora|nuria|olga|paula|rosa|sandra|sara|silvia|sofia|sonia|teresa|victoria)$'
    OR LOWER(SUBSTRING_INDEX(nombre, ' ', 1)) REGEXP '(a|ia)$' THEN 'femenino'
  ELSE 'masculino' END WHERE sexo IS NULL;
ALTER TABLE atletas MODIFY sexo ENUM('masculino', 'femenino') NOT NULL;

ALTER TABLE pruebas DROP INDEX uq_pruebas_nombre,
  ADD COLUMN ambito ENUM('pista_cubierta', 'aire_libre', 'ruta') NOT NULL DEFAULT 'aire_libre' AFTER nombre,
  ADD COLUMN grupo VARCHAR(40) NOT NULL DEFAULT 'Curses' AFTER ambito,
  ADD COLUMN informacion_adicional BOOLEAN NOT NULL DEFAULT FALSE AFTER sentido_resultado,
  ADD UNIQUE KEY uq_pruebas_catalogo (ambito, grupo, nombre);

CREATE TABLE IF NOT EXISTS ciudades (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, nombre VARCHAR(140) NOT NULL,
  provincia VARCHAR(100) NOT NULL DEFAULT '', creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id), UNIQUE KEY uq_ciudades_nombre_provincia (nombre, provincia)
) ENGINE=InnoDB;
INSERT IGNORE INTO ciudades (nombre) SELECT DISTINCT localidad FROM pistas WHERE localidad <> '';

ALTER TABLE marcas MODIFY pista_id BIGINT UNSIGNED NULL, MODIFY categoria VARCHAR(40) NOT NULL,
  ADD COLUMN ciudad_id BIGINT UNSIGNED NULL AFTER pista_id,
  ADD COLUMN nombre_pista VARCHAR(160) NULL AFTER ciudad_id,
  ADD COLUMN caracteristica_tecnica VARCHAR(255) NULL AFTER resultado,
  ADD KEY idx_marcas_ciudad (ciudad_id),
  ADD CONSTRAINT fk_marcas_ciudad FOREIGN KEY (ciudad_id) REFERENCES ciudades (id);
UPDATE marcas m JOIN pistas p ON p.id = m.pista_id JOIN ciudades c ON c.nombre = p.localidad
SET m.ciudad_id = c.id, m.nombre_pista = p.nombre WHERE m.ciudad_id IS NULL;
ALTER TABLE marcas MODIFY ciudad_id BIGINT UNSIGNED NOT NULL;

CREATE TABLE IF NOT EXISTS traducciones (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, literal VARCHAR(255) NOT NULL,
  castellano VARCHAR(255) NOT NULL, catalan VARCHAR(255) NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id), UNIQUE KEY uq_traducciones_literal (literal)
) ENGINE=InnoDB;

UPDATE marcas m JOIN atletas a ON a.id = m.atleta_id SET m.categoria = CONCAT(
  CASE WHEN TIMESTAMPDIFF(YEAR, a.fecha_nacimiento, m.fecha) < 8 THEN 'Sub8'
    WHEN TIMESTAMPDIFF(YEAR, a.fecha_nacimiento, m.fecha) < 10 THEN 'Sub10'
    WHEN TIMESTAMPDIFF(YEAR, a.fecha_nacimiento, m.fecha) < 12 THEN 'Sub12'
    WHEN TIMESTAMPDIFF(YEAR, a.fecha_nacimiento, m.fecha) < 14 THEN 'Sub14'
    WHEN TIMESTAMPDIFF(YEAR, a.fecha_nacimiento, m.fecha) < 16 THEN 'Sub16'
    WHEN TIMESTAMPDIFF(YEAR, a.fecha_nacimiento, m.fecha) < 18 THEN 'Sub18'
    WHEN TIMESTAMPDIFF(YEAR, a.fecha_nacimiento, m.fecha) < 20 THEN 'Sub20'
    WHEN TIMESTAMPDIFF(YEAR, a.fecha_nacimiento, m.fecha) < 23 THEN 'Sub23'
    WHEN TIMESTAMPDIFF(YEAR, a.fecha_nacimiento, m.fecha) < 35 THEN 'Senior'
    ELSE CONCAT('Master ', LEAST(100, FLOOR(TIMESTAMPDIFF(YEAR, a.fecha_nacimiento, m.fecha) / 5) * 5)) END,
  ' - ', IF(a.sexo = 'femenino', 'Femenino', 'Masculino'));
