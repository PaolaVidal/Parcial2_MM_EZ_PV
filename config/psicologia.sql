-- Crear base de datos
CREATE DATABASE IF NOT EXISTS psicologia;
USE psicologia;

-- ===========================
-- 0. USUARIOS
-- ===========================
CREATE TABLE Usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    passwordd VARCHAR(100) NOT NULL,
    rol ENUM('admin', 'paciente', 'psicologo') DEFAULT 'paciente',
    estado ENUM('activo', 'inactivo') DEFAULT 'activo'
);

-- ===========================
-- 1. PACIENTES
-- ===========================
CREATE TABLE Paciente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    fecha_nacimiento DATE,
    genero ENUM('masculino', 'femenino', 'otro'),
    correo VARCHAR(100),
    direccion VARCHAR(255),
    telefono VARCHAR(20),
    historial_clinico TEXT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    CONSTRAINT fk_paciente_usuario FOREIGN KEY (id_usuario)
        REFERENCES Usuario(id) ON UPDATE CASCADE ON DELETE CASCADE
);

-- ===========================
-- 2. PSICÓLOGOS
-- ===========================
CREATE TABLE Psicologo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    especialidad VARCHAR(100),
    experiencia TEXT,
    horario TEXT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    CONSTRAINT fk_psicologo_usuario FOREIGN KEY (id_usuario)
        REFERENCES Usuario(id) ON UPDATE CASCADE ON DELETE CASCADE
);

-- ===========================
-- 3. CITAS (con QR)
-- ===========================
CREATE TABLE Cita (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT NOT NULL,
    id_psicologo INT NOT NULL,
    fecha_hora DATETIME NOT NULL,
    estado_cita ENUM('pendiente', 'realizada', 'cancelada') DEFAULT 'pendiente',
    motivo_consulta TEXT,
    qr_code VARCHAR(255) UNIQUE, -- QR generado al crear la cita
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    CONSTRAINT fk_cita_paciente FOREIGN KEY (id_paciente)
        REFERENCES Paciente(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_cita_psicologo FOREIGN KEY (id_psicologo)
        REFERENCES Psicologo(id) ON UPDATE CASCADE ON DELETE CASCADE
);

-- ===========================
-- 4. PAGOS (simulados)
-- ===========================
CREATE TABLE Pago (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_cita INT NOT NULL,
    monto_base DECIMAL(10,2) NOT NULL,
    monto_total DECIMAL(10,2) NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado_pago ENUM('pendiente', 'pagado') DEFAULT 'pendiente',
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    CONSTRAINT fk_pago_cita FOREIGN KEY (id_cita)
        REFERENCES Cita(id) ON UPDATE CASCADE ON DELETE CASCADE
);

-- ===========================
-- 5. TICKETS DE PAGO (con QR)
-- ===========================
CREATE TABLE Ticket_Pago (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pago INT NOT NULL,
    codigo VARCHAR(50) UNIQUE,
    fecha_emision DATETIME DEFAULT CURRENT_TIMESTAMP,
    numero_ticket VARCHAR(50) UNIQUE,
    qr_code VARCHAR(255) UNIQUE, -- QR del ticket
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    CONSTRAINT fk_ticket_pago FOREIGN KEY (id_pago)
        REFERENCES Pago(id) ON UPDATE CASCADE ON DELETE CASCADE
);

-- ===========================
-- 6. EVALUACIONES
-- ===========================
CREATE TABLE Evaluacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_cita INT NOT NULL,
    estado_emocional INT CHECK (estado_emocional BETWEEN 1 AND 10),
    comentarios TEXT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    CONSTRAINT fk_evaluacion_cita FOREIGN KEY (id_cita)
        REFERENCES Cita(id) ON UPDATE CASCADE ON DELETE CASCADE
);