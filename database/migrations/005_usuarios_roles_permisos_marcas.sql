ALTER TABLE usuarios
  ADD COLUMN rol ENUM('admin', 'normal') NOT NULL DEFAULT 'admin' AFTER activo;

CREATE TABLE IF NOT EXISTS usuario_atleta_permisos (
  usuario_id BIGINT UNSIGNED NOT NULL,
  atleta_id BIGINT UNSIGNED NOT NULL,
  puede_crear BOOLEAN NOT NULL DEFAULT FALSE,
  puede_editar BOOLEAN NOT NULL DEFAULT FALSE,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (usuario_id, atleta_id),
  KEY idx_usuario_atleta_permisos_atleta (atleta_id),
  CONSTRAINT fk_usuario_atleta_permisos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
  CONSTRAINT fk_usuario_atleta_permisos_atleta FOREIGN KEY (atleta_id) REFERENCES atletas (id) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE marcas
  ADD COLUMN creado_por BIGINT UNSIGNED NULL AFTER categoria,
  ADD COLUMN actualizado_por BIGINT UNSIGNED NULL AFTER creado_por,
  ADD KEY idx_marcas_creado_por (creado_por),
  ADD KEY idx_marcas_actualizado_por (actualizado_por),
  ADD CONSTRAINT fk_marcas_creado_por FOREIGN KEY (creado_por) REFERENCES usuarios (id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_marcas_actualizado_por FOREIGN KEY (actualizado_por) REFERENCES usuarios (id) ON DELETE SET NULL;

UPDATE marcas
SET creado_por = COALESCE((SELECT id FROM usuarios WHERE usuario = 'admin' LIMIT 1), (SELECT id FROM usuarios ORDER BY id LIMIT 1)),
    actualizado_por = COALESCE((SELECT id FROM usuarios WHERE usuario = 'admin' LIMIT 1), (SELECT id FROM usuarios ORDER BY id LIMIT 1))
WHERE creado_por IS NULL OR actualizado_por IS NULL;
