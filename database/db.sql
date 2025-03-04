-- Crear la base de datos
CREATE DATABASE sistema_transporte CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE sistema_transporte;

-- Tabla de vehículos
CREATE TABLE vehiculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    placa VARCHAR(20) NOT NULL UNIQUE,
    modelo VARCHAR(50),
    capacidad INT,
    estado ENUM('Activo', 'Inactivo') DEFAULT 'Activo',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de motoristas
CREATE TABLE motoristas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    dui VARCHAR(15) UNIQUE,
    telefono VARCHAR(15),
    licencia VARCHAR(20),
    estado ENUM('Activo', 'Inactivo') DEFAULT 'Activo',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de rutas
CREATE TABLE rutas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(10) NOT NULL,
    origen VARCHAR(100) NOT NULL,
    destino VARCHAR(100) NOT NULL,
    vehiculo_id INT NOT NULL,
    motorista_id INT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_inicio_refuerzo DATE NOT NULL,
    estado ENUM('Activa', 'Inactiva') DEFAULT 'Activa',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE RESTRICT,
    FOREIGN KEY (motorista_id) REFERENCES motoristas(id) ON DELETE RESTRICT
);

-- Tabla de días de trabajo
CREATE TABLE dias_trabajo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ruta_id INT NOT NULL,
    fecha DATE NOT NULL,
    tipo ENUM('Trabajo', 'Descanso', 'Refuerzo') NOT NULL,
    contador_ciclo INT NOT NULL,
    monto DECIMAL(10,2) DEFAULT NULL,
    estado_entrega ENUM('Pendiente', 'Recibido') DEFAULT NULL,
    observaciones TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ruta_id) REFERENCES rutas(id) ON DELETE CASCADE,
    UNIQUE KEY unique_ruta_fecha (ruta_id, fecha)
);

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('Administrador', 'Usuario') NOT NULL DEFAULT 'Usuario',
    estado ENUM('Activo', 'Inactivo') DEFAULT 'Activo',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar el usuario administrador (usuario: Cristian, contraseña: Lh01136078)
INSERT INTO usuarios (nombre, apellido, usuario, password, rol) 
VALUES ('Cristian', 'Admin', 'Cristian', '$2y$10$7KCnfTxmJ9CrW71JlBdRdedE0tGdB0f0Vr27xSbpwawFrh4gvW.tq', 'Administrador');