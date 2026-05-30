ALTER TABLE usuarios
  MODIFY COLUMN rol ENUM('admin', 'normal', 'marks_manager') NOT NULL DEFAULT 'admin';
