-- Creación de la base de datos
CREATE DATABASE IF NOT EXISTS sistema_transporte;
USE sistema_transporte;

-- Tabla de vehículos
CREATE TABLE IF NOT EXISTS vehiculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    placa VARCHAR(20) NOT NULL UNIQUE,
    modelo VARCHAR(50),
    capacidad INT,
    estado ENUM('Activo', 'Inactivo') DEFAULT 'Activo',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de motoristas
CREATE TABLE IF NOT EXISTS motoristas (
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
CREATE TABLE IF NOT EXISTS rutas (
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
CREATE TABLE IF NOT EXISTS dias_trabajo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ruta_id INT NOT NULL,
    fecha DATE NOT NULL,
    tipo ENUM('Trabajo', 'Descanso', 'Refuerzo') NOT NULL,
    contador_ciclo INT NOT NULL, -- Para llevar el control del ciclo (1-5)
    monto DECIMAL(10,2) DEFAULT NULL,
    estado_entrega ENUM('Pendiente', 'Recibido') DEFAULT NULL,
    observaciones TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ruta_id) REFERENCES rutas(id) ON DELETE CASCADE,
    UNIQUE KEY unique_ruta_fecha (ruta_id, fecha)
);

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('Administrador', 'Usuario') NOT NULL DEFAULT 'Usuario',
    estado ENUM('Activo', 'Inactivo') DEFAULT 'Activo',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar usuario administrador por defecto (password: admin123)
INSERT INTO usuarios (nombre, apellido, usuario, password, rol) 
VALUES ('Admin', 'Sistema', 'admin', '$2y$10$uPZ7wHnLBoLUa97KjpmgAutvsgJNL9bRpZ5KzBNnQA3mutLx.jpwO', 'Administrador');