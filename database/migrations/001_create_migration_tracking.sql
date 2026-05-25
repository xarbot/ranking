-- Incorpora el registro de migraciones a instalaciones existentes.
-- No modifica las tablas funcionales ni los datos de la aplicacion.

CREATE TABLE IF NOT EXISTS schema_migrations (
  version VARCHAR(255) NOT NULL,
  executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (version)
) ENGINE=InnoDB;
