CREATE DATABASE IF NOT EXISTS talleres_cocina
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE talleres_cocina;

CREATE TABLE IF NOT EXISTS aprendiz (
    matricula VARCHAR(20) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    edad TINYINT UNSIGNED NOT NULL,
    correo VARCHAR(150) NOT NULL,
    PRIMARY KEY (matricula),
    UNIQUE KEY uq_aprendiz_correo (correo)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS chef (
    id_chef INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    especialidad VARCHAR(100) NOT NULL,
    identificacion_profesional VARCHAR(50) NOT NULL,
    PRIMARY KEY (id_chef),
    UNIQUE KEY uq_chef_identificacion (identificacion_profesional)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS taller (
    id_taller INT UNSIGNED NOT NULL AUTO_INCREMENT,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    nivel VARCHAR(30) NOT NULL,
    id_chef INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_taller),
    KEY idx_taller_id_chef (id_chef),
    CONSTRAINT fk_taller_chef
        FOREIGN KEY (id_chef) REFERENCES chef (id_chef)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inscripcion (
    id_inscripcion INT UNSIGNED NOT NULL AUTO_INCREMENT,
    matricula VARCHAR(20) NOT NULL,
    id_taller INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_inscripcion),
    UNIQUE KEY uq_inscripcion_aprendiz_taller (matricula, id_taller),
    KEY idx_inscripcion_id_taller (id_taller),
    CONSTRAINT fk_inscripcion_aprendiz
        FOREIGN KEY (matricula) REFERENCES aprendiz (matricula)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_inscripcion_taller
        FOREIGN KEY (id_taller) REFERENCES taller (id_taller)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;
