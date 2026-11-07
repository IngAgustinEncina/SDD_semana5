CREATE DATABASE IF NOT EXISTS app_comentarios_v1
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE app_comentarios_v1;

CREATE TABLE comentarios (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  texto TEXT NOT NULL,
  autor VARCHAR(60) NOT NULL,
  fecha_creacion DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_comentarios_orden (fecha_creacion, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE respuestas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  comentario_id INT UNSIGNED NOT NULL,
  texto TEXT NOT NULL,
  autor VARCHAR(60) NOT NULL,
  fecha_creacion DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_respuestas_orden (comentario_id, fecha_creacion, id),
  CONSTRAINT fk_respuestas_comentario
    FOREIGN KEY (comentario_id) REFERENCES comentarios (id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
