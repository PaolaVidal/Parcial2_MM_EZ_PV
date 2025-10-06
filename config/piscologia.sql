-- ===========================================
-- BASE DE DATOS: psicologia
-- ===========================================
CREATE DATABASE IF NOT EXISTS psicologia;
USE psicologia;

-- ============================
-- 1. USUARIOS
-- ============================
CREATE TABLE Usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    passwordd VARCHAR(100) NOT NULL,
    rol ENUM('admin', 'psicologo') NOT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo'
);

-- ============================
-- 2. PSICÓLOGOS
-- ============================
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

-- ============================
-- 3. PACIENTES (sin credenciales)
-- ============================
CREATE TABLE Paciente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dui VARCHAR(20) UNIQUE,
    codigo_acceso VARCHAR(64) UNIQUE, -- usado para verificar identidad en citas
    fecha_nacimiento DATE,
    genero ENUM('masculino', 'femenino', 'otro'),
    correo VARCHAR(100),
    direccion VARCHAR(255),
    telefono VARCHAR(20),
    historial_clinico TEXT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo'
);

-- ============================
-- 4. CITAS (con QR)
-- ============================
CREATE TABLE Cita (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT NOT NULL,
    id_psicologo INT NOT NULL,
    fecha_hora DATETIME NOT NULL,
    estado_cita ENUM('pendiente', 'realizada', 'cancelada') DEFAULT 'pendiente',
    motivo_consulta TEXT,
    qr_code VARCHAR(255) UNIQUE,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    CONSTRAINT fk_cita_paciente FOREIGN KEY (id_paciente)
        REFERENCES Paciente(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_cita_psicologo FOREIGN KEY (id_psicologo)
        REFERENCES Psicologo(id) ON UPDATE CASCADE ON DELETE CASCADE
);

-- ============================
-- 5. PAGOS (simulados)
-- ============================
CREATE TABLE Pago (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_cita INT NOT NULL,
    monto_base DECIMAL(10,2),  -- tarifa base (ej. 35)
    monto_total DECIMAL(10,2), -- base + extras (si los hay)
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado_pago ENUM('pendiente', 'pagado') DEFAULT 'pendiente',
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    CONSTRAINT fk_pago_cita FOREIGN KEY (id_cita)
        REFERENCES Cita(id) ON UPDATE CASCADE ON DELETE CASCADE
);

-- ============================
-- 6. TICKETS DE PAGO (con QR)
-- ============================
CREATE TABLE Ticket_Pago (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pago INT NOT NULL,
    codigo VARCHAR(50) UNIQUE,
    fecha_emision DATETIME DEFAULT CURRENT_TIMESTAMP,
    numero_ticket VARCHAR(50) UNIQUE,
    qr_code VARCHAR(255) UNIQUE,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    CONSTRAINT fk_ticket_pago FOREIGN KEY (id_pago)
        REFERENCES Pago(id) ON UPDATE CASCADE ON DELETE CASCADE
);

-- ============================
-- 7. EVALUACIONES
-- ============================
CREATE TABLE Evaluacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_cita INT NOT NULL,
    estado_emocional INT CHECK (estado_emocional BETWEEN 1 AND 10),
    comentarios TEXT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    CONSTRAINT fk_evaluacion_cita FOREIGN KEY (id_cita)
        REFERENCES Cita(id) ON UPDATE CASCADE ON DELETE CASCADE
);

-- ============================
-- 8. SOLICITUD DE CAMBIO
-- ============================
CREATE TABLE SolicitudCambio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT NOT NULL,
    campo VARCHAR(50) NOT NULL,
    valor_nuevo TEXT NOT NULL,
    estado ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'pendiente',
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sc_paciente FOREIGN KEY (id_paciente)
        REFERENCES Paciente(id) ON DELETE CASCADE
);
