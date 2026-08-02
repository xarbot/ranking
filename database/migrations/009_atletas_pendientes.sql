-- Permite representar atletas históricos incompletos creados desde importaciones masivas.
-- Los atletas existentes quedan como completos por el DEFAULT y no se recalculan marcas.

ALTER TABLE atletas
  MODIFY COLUMN fecha_nacimiento DATE NULL,
  MODIFY COLUMN sexo ENUM('masculino', 'femenino') NULL,
  ADD COLUMN estado ENUM('completo', 'pendiente') NOT NULL DEFAULT 'completo' AFTER sexo;

ALTER TABLE marcas
  MODIFY COLUMN categoria VARCHAR(40) NULL;

ALTER TABLE marcas_borradas
  MODIFY COLUMN categoria VARCHAR(40) NULL;
