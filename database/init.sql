-- Ejecutar sobre una base de datos vacia ya creada para la aplicacion.

CREATE TABLE IF NOT EXISTS usuarios (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(180) NOT NULL,
  usuario VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  activo BOOLEAN NOT NULL DEFAULT TRUE,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_usuario (usuario)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS atletas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  apellidos VARCHAR(180) NOT NULL,
  fecha_nacimiento DATE NOT NULL,
  sexo ENUM('masculino', 'femenino') NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_atletas_nombre_apellidos (nombre, apellidos)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pruebas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(120) NOT NULL,
  ambito ENUM('pista_cubierta', 'aire_libre', 'ruta') NOT NULL,
  grupo VARCHAR(40) NOT NULL,
  sentido_resultado ENUM('menor', 'mayor') NOT NULL DEFAULT 'menor',
  informacion_adicional BOOLEAN NOT NULL DEFAULT FALSE,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pruebas_catalogo (ambito, grupo, nombre)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pistas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(160) NOT NULL,
  localidad VARCHAR(120) NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pistas_nombre_localidad (nombre, localidad)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ciudades (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(140) NOT NULL,
  provincia VARCHAR(100) NOT NULL DEFAULT '',
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ciudades_nombre_provincia (nombre, provincia)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS marcas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  atleta_id BIGINT UNSIGNED NOT NULL,
  prueba_id BIGINT UNSIGNED NOT NULL,
  pista_id BIGINT UNSIGNED NULL,
  ciudad_id BIGINT UNSIGNED NOT NULL,
  nombre_pista VARCHAR(160) NULL,
  fecha DATE NOT NULL,
  resultado VARCHAR(40) NOT NULL,
  caracteristica_tecnica VARCHAR(255) NULL,
  categoria VARCHAR(40) NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_marcas_atleta_fecha (atleta_id, fecha),
  KEY idx_marcas_prueba_categoria (prueba_id, categoria),
  CONSTRAINT fk_marcas_atleta FOREIGN KEY (atleta_id) REFERENCES atletas (id),
  CONSTRAINT fk_marcas_prueba FOREIGN KEY (prueba_id) REFERENCES pruebas (id),
  CONSTRAINT fk_marcas_pista FOREIGN KEY (pista_id) REFERENCES pistas (id),
  CONSTRAINT fk_marcas_ciudad FOREIGN KEY (ciudad_id) REFERENCES ciudades (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS traducciones (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  literal VARCHAR(255) NOT NULL,
  castellano VARCHAR(255) NOT NULL,
  catalan VARCHAR(255) NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_traducciones_literal (literal)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS schema_migrations (
  version VARCHAR(255) NOT NULL,
  executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (version)
) ENGINE=InnoDB;

INSERT IGNORE INTO schema_migrations (version)
VALUES ('001_create_migration_tracking.sql'),
       ('002_add_pruebas_sentido_resultado.sql'),
       ('003_clasificacion_sexo_catalogos_ciudades_traducciones.sql'),
       ('004_catalogo_pruebas_cerrado.sql');
