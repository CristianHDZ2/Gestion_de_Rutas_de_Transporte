-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `sistema_transporte` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `sistema_transporte`;

-- Drop tables if they exist (in the correct order to avoid foreign key constraints)
DROP TABLE IF EXISTS `dias_trabajo`;
DROP TABLE IF EXISTS `rutas`;
DROP TABLE IF EXISTS `motoristas`;
DROP TABLE IF EXISTS `vehiculos`;
DROP TABLE IF EXISTS `usuarios`;

-- Create tables with correct structure
CREATE TABLE IF NOT EXISTS `vehiculos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `placa` varchar(20) NOT NULL,
  `modelo` varchar(50) DEFAULT NULL,
  `capacidad` int DEFAULT NULL,
  `estado` enum('Activo','Inactivo') DEFAULT 'Activo',
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `placa` (`placa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `motoristas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `dui` varchar(15) DEFAULT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `licencia` varchar(20) DEFAULT NULL,
  `estado` enum('Activo','Inactivo') DEFAULT 'Activo',
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dui` (`dui`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `rutas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero` varchar(10) NOT NULL,
  `origen` varchar(100) NOT NULL,
  `destino` varchar(100) NOT NULL,
  `vehiculo_id` int NOT NULL,
  `motorista_id` int NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_inicio_refuerzo` date NOT NULL,
  `estado` enum('Activa','Inactiva') DEFAULT 'Activa',
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `vehiculo_id` (`vehiculo_id`),
  KEY `motorista_id` (`motorista_id`),
  CONSTRAINT `rutas_ibfk_1` FOREIGN KEY (`vehiculo_id`) REFERENCES `vehiculos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `rutas_ibfk_2` FOREIGN KEY (`motorista_id`) REFERENCES `motoristas` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `dias_trabajo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ruta_id` int NOT NULL,
  `fecha` date NOT NULL,
  `tipo` enum('Trabajo','Descanso','Refuerzo') NOT NULL,
  `contador_ciclo` int DEFAULT NULL,
  `monto` decimal(10,2) DEFAULT NULL,
  `estado_entrega` enum('Pendiente','Recibido') DEFAULT NULL,
  `observaciones` text,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ruta_fecha` (`ruta_id`,`fecha`),
  CONSTRAINT `dias_trabajo_ibfk_1` FOREIGN KEY (`ruta_id`) REFERENCES `rutas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('Administrador','Usuario') NOT NULL DEFAULT 'Usuario',
  `estado` enum('Activo','Inactivo') DEFAULT 'Activo',
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Insert the admin user (password "Lh01136078" is hashed with bcrypt)
INSERT INTO `usuarios` (`nombre`, `apellido`, `usuario`, `password`, `rol`, `estado`) 
VALUES ('Cristian', 'Administrador', 'Cristian', '$2y$10$1kLwUFY25GqNHOgGxfQ/5.B3RxWgKSrQvO8q3dMo5WXB7VzYaZOvK', 'Administrador', 'Activo');