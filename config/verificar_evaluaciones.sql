-- Script de verificación y creación de tabla Evaluacion
-- Ejecutar este script si la tabla no existe en tu base de datos

USE psicologia2;

-- Verificar si la tabla existe
SELECT 
    CASE 
        WHEN EXISTS (
            SELECT * FROM information_schema.tables 
            WHERE table_schema = 'psicologia2' 
            AND table_name = 'Evaluacion'
        ) 
        THEN 'La tabla Evaluacion YA EXISTE' 
        ELSE 'La tabla Evaluacion NO EXISTE - se creará ahora'
    END AS estado;

-- Crear tabla si no existe
CREATE TABLE IF NOT EXISTS Evaluacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_cita INT NOT NULL,
    estado_emocional INT CHECK (estado_emocional BETWEEN 1 AND 10),
    comentarios TEXT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    CONSTRAINT fk_evaluacion_cita FOREIGN KEY (id_cita)
        REFERENCES Cita(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verificar estructura de la tabla
DESCRIBE Evaluacion;

-- Contar registros existentes
SELECT COUNT(*) as total_evaluaciones FROM Evaluacion;

-- Ejemplo de inserción de prueba (comentado - descomentar si deseas probar)
/*
INSERT INTO Evaluacion (id_cita, estado_emocional, comentarios) 
VALUES 
    (1, 7, 'Primera sesión: El paciente mostró buena disposición para el tratamiento.'),
    (1, 8, 'Segunda evaluación de la misma sesión: Se observa mejoría en el estado de ánimo.'),
    (2, 6, 'Paciente presenta ansiedad moderada. Se recomienda continuar con terapia.');
*/

-- Consulta de ejemplo: Ver evaluaciones con datos de la cita
SELECT 
    e.id,
    e.id_cita,
    c.fecha_hora,
    c.estado_cita,
    e.estado_emocional,
    e.comentarios,
    p.nombre as nombre_paciente
FROM Evaluacion e
JOIN Cita c ON c.id = e.id_cita
JOIN Paciente p ON p.id = c.id_paciente
WHERE e.estado = 'activo'
ORDER BY e.id DESC
LIMIT 10;

-- Estadísticas de evaluaciones por psicólogo
SELECT 
    ps.id as id_psicologo,
    u.nombre as nombre_psicologo,
    COUNT(DISTINCT c.id) as total_citas_con_evaluaciones,
    COUNT(e.id) as total_evaluaciones,
    ROUND(AVG(e.estado_emocional), 2) as promedio_estado_emocional,
    MIN(e.estado_emocional) as estado_minimo,
    MAX(e.estado_emocional) as estado_maximo
FROM Psicologo ps
JOIN Usuario u ON u.id = ps.id_usuario
LEFT JOIN Cita c ON c.id_psicologo = ps.id
LEFT JOIN Evaluacion e ON e.id_cita = c.id AND e.estado = 'activo'
WHERE ps.estado = 'activo'
GROUP BY ps.id, u.nombre
ORDER BY total_evaluaciones DESC;

-- Verificación final
SELECT 'Tabla Evaluacion lista para usar!' as mensaje;
