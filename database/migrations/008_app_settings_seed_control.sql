-- Configuración persistente mínima para controlar comportamientos automáticos.
-- No se usa schema_migrations como almacén de preferencias funcionales.

CREATE TABLE IF NOT EXISTS app_settings (
  name VARCHAR(100) NOT NULL,
  value VARCHAR(255) NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (name)
) ENGINE=InnoDB;

INSERT INTO app_settings (name, value)
VALUES ('seed_cities_enabled', '1')
ON DUPLICATE KEY UPDATE value = value;
