CREATE DATABASE IF NOT EXISTS bookgo_lite CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bookgo_lite;

CREATE TABLE roles (
  id_rol INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(20) NOT NULL UNIQUE
);

CREATE TABLE usuarios (
  id_usuario INT AUTO_INCREMENT PRIMARY KEY,
  id_rol INT NOT NULL,
  nombre VARCHAR(60) NOT NULL,
  apellido VARCHAR(60) NOT NULL,
  correo VARCHAR(120) NOT NULL UNIQUE,
  contrasena VARCHAR(255) NOT NULL,
  estado TINYINT NOT NULL DEFAULT 1,
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
);

CREATE TABLE categorias (
  id_categoria INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL UNIQUE,
  descripcion VARCHAR(255),
  estado TINYINT NOT NULL DEFAULT 1
);

CREATE TABLE servicios (
  id_servicio INT AUTO_INCREMENT PRIMARY KEY,
  id_categoria INT NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  descripcion TEXT,
  precio DECIMAL(8,2) NOT NULL CHECK (precio >= 0),
  duracion_min INT NOT NULL CHECK (duracion_min > 0),
  estado TINYINT NOT NULL DEFAULT 1,
  FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
);

CREATE TABLE horarios (
  id_horario INT AUTO_INCREMENT PRIMARY KEY,
  id_servicio INT NOT NULL,
  fecha DATE NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fin TIME NOT NULL,
  cupos_total INT NOT NULL CHECK (cupos_total > 0),
  cupos_disponibles INT NOT NULL,
  estado TINYINT NOT NULL DEFAULT 1,
  CHECK (hora_fin > hora_inicio),
  CHECK (cupos_disponibles >= 0 AND cupos_disponibles <= cupos_total),
  FOREIGN KEY (id_servicio) REFERENCES servicios(id_servicio)
);

CREATE TABLE reservas (
  id_reserva INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  id_horario INT NOT NULL,
  estado ENUM('PENDIENTE','CONFIRMADA','REALIZADA','CANCELADA') NOT NULL DEFAULT 'PENDIENTE',
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
  FOREIGN KEY (id_horario) REFERENCES horarios(id_horario)
);

CREATE TABLE resenas (
  id_resena INT AUTO_INCREMENT PRIMARY KEY,
  id_reserva INT NOT NULL UNIQUE,
  id_usuario INT NOT NULL,
  calificacion TINYINT NOT NULL CHECK (calificacion BETWEEN 1 AND 5),
  comentario VARCHAR(500) NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_reserva) REFERENCES reservas(id_reserva),
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

INSERT INTO roles (nombre) VALUES ('ADMIN'), ('CLIENTE');

INSERT INTO categorias (nombre, descripcion) VALUES
('Reparación y mantenimiento', 'Arreglo y cuidado de computadoras y laptops'),
('Redes y conectividad', 'Instalación y configuración de redes'),
('Ciberseguridad personal', 'Protección de tus dispositivos y cuentas');

INSERT INTO servicios (id_categoria, nombre, descripcion, precio, duracion_min) VALUES
(1, 'Mantenimiento preventivo de PC', 'Limpieza interna, pasta térmica y revisión general.', 35.00, 60),
(1, 'Cambio de pantalla de laptop', 'Reemplazo de pantalla dañada con repuesto compatible.', 60.00, 90),
(2, 'Instalación de red Wi-Fi', 'Instalación y optimización de la red inalámbrica del hogar.', 45.00, 90),
(2, 'Configuración de router', 'Configuración segura de router y cambio de claves.', 25.00, 45),
(3, 'Revisión de seguridad de dispositivos', 'Revisión de actualizaciones, contraseñas y configuración.', 40.00, 60),
(3, 'Eliminación de virus y malware', 'Detección y limpieza de software malicioso.', 30.00, 60);

INSERT INTO horarios (id_servicio, fecha, hora_inicio, hora_fin, cupos_total, cupos_disponibles) VALUES
(1, '2026-11-20', '09:00', '10:00', 3, 3),
(3, '2026-11-21', '10:00', '11:30', 2, 2),
(5, '2026-11-23', '14:00', '15:00', 4, 4);
