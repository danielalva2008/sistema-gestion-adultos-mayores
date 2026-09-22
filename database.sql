-- ============================================================
-- SISTEMA DE GESTIÓN DE RESIDENCIA PARA ADULTOS MAYORES
-- Proyecto académico: Desarrollo de Aplicaciones Basado en Plataformas
-- PHP 8.x + MySQL 8.x + XAMPP
-- Datos de prueba sintéticos para uso académico
-- ============================================================

DROP DATABASE IF EXISTS sistema_residencia;
CREATE DATABASE sistema_residencia
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sistema_residencia;

-- ============================================================
-- USUARIO DE APLICACIÓN
-- En XAMPP normalmente root no tiene contraseña.
-- Este usuario NO es el usuario administrador de la aplicación.
-- ============================================================
CREATE USER IF NOT EXISTS 'residencia_app'@'localhost'
IDENTIFIED BY 'Residencia2026*';

GRANT SELECT, INSERT, UPDATE, DELETE
ON sistema_residencia.*
TO 'residencia_app'@'localhost';

FLUSH PRIVILEGES;

-- ============================================================
-- TABLAS
-- ============================================================

CREATE TABLE roles (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(200),
    estado ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO'
) ENGINE=InnoDB;

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    id_rol INT NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    email VARCHAR(120) UNIQUE,
    estado ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_rol
        FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
) ENGINE=InnoDB;

CREATE TABLE habitaciones (
    id_habitacion INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(10) NOT NULL UNIQUE,
    piso INT NOT NULL,
    capacidad INT NOT NULL,
    estado ENUM('DISPONIBLE','OCUPADA','MANTENIMIENTO') NOT NULL DEFAULT 'DISPONIBLE',
    observaciones VARCHAR(255),
    CHECK (capacidad > 0),
    CHECK (piso > 0)
) ENGINE=InnoDB;

CREATE TABLE residentes (
    id_residente INT AUTO_INCREMENT PRIMARY KEY,
    id_habitacion INT NULL,
    codigo_residente VARCHAR(20) NOT NULL UNIQUE,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    sexo ENUM('F','M') NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    estado_civil ENUM('SOLTERO','CASADO','VIUDO','DIVORCIADO','CONVIVIENTE') NOT NULL,
    telefono VARCHAR(20),
    distrito VARCHAR(80),
    provincia VARCHAR(80),
    departamento VARCHAR(80),
    fecha_ingreso DATE NOT NULL,
    contacto_emergencia VARCHAR(150),
    telefono_emergencia VARCHAR(20),
    estado ENUM('ACTIVO','INACTIVO','EGRESADO') NOT NULL DEFAULT 'ACTIVO',
    observaciones VARCHAR(255),
    CONSTRAINT fk_residente_habitacion
        FOREIGN KEY (id_habitacion) REFERENCES habitaciones(id_habitacion)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE personal (
    id_personal INT AUTO_INCREMENT PRIMARY KEY,
    codigo_personal VARCHAR(20) NOT NULL UNIQUE,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    cargo VARCHAR(80) NOT NULL,
    especialidad VARCHAR(100),
    telefono VARCHAR(20),
    email VARCHAR(120),
    fecha_ingreso DATE NOT NULL,
    estado ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO'
) ENGINE=InnoDB;

CREATE TABLE tipos_incidente (
    id_tipo_incidente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL UNIQUE,
    descripcion VARCHAR(255),
    nivel_riesgo ENUM('BAJO','MEDIO','ALTO','CRITICO') NOT NULL
) ENGINE=InnoDB;

CREATE TABLE incidentes (
    id_incidente INT AUTO_INCREMENT PRIMARY KEY,
    id_residente INT NOT NULL,
    id_tipo_incidente INT NOT NULL,
    id_personal_reporta INT NULL,
    fecha_hora DATETIME NOT NULL,
    lugar VARCHAR(150) NOT NULL,
    descripcion TEXT NOT NULL,
    accion_realizada TEXT,
    requiere_seguimiento BOOLEAN NOT NULL DEFAULT FALSE,
    estado ENUM('REGISTRADO','EN_SEGUIMIENTO','CERRADO') NOT NULL DEFAULT 'REGISTRADO',
    fecha_cierre DATETIME NULL,
    CONSTRAINT fk_incidente_residente
        FOREIGN KEY (id_residente) REFERENCES residentes(id_residente),
    CONSTRAINT fk_incidente_tipo
        FOREIGN KEY (id_tipo_incidente) REFERENCES tipos_incidente(id_tipo_incidente),
    CONSTRAINT fk_incidente_personal
        FOREIGN KEY (id_personal_reporta) REFERENCES personal(id_personal)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE actividades (
    id_actividad INT AUTO_INCREMENT PRIMARY KEY,
    id_personal_responsable INT NULL,
    nombre VARCHAR(120) NOT NULL,
    tipo VARCHAR(80) NOT NULL,
    descripcion VARCHAR(255),
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    lugar VARCHAR(120) NOT NULL,
    cupo INT NOT NULL,
    estado ENUM('PROGRAMADA','REALIZADA','CANCELADA') NOT NULL DEFAULT 'PROGRAMADA',
    CONSTRAINT fk_actividad_personal
        FOREIGN KEY (id_personal_responsable) REFERENCES personal(id_personal)
        ON DELETE SET NULL,
    CHECK (cupo > 0),
    CHECK (hora_fin > hora_inicio)
) ENGINE=InnoDB;

CREATE TABLE participaciones (
    id_participacion INT AUTO_INCREMENT PRIMARY KEY,
    id_actividad INT NOT NULL,
    id_residente INT NOT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    asistencia ENUM('PENDIENTE','ASISTIO','NO_ASISTIO') NOT NULL DEFAULT 'PENDIENTE',
    observaciones VARCHAR(255),
    UNIQUE KEY uk_actividad_residente (id_actividad, id_residente),
    CONSTRAINT fk_participacion_actividad
        FOREIGN KEY (id_actividad) REFERENCES actividades(id_actividad)
        ON DELETE CASCADE,
    CONSTRAINT fk_participacion_residente
        FOREIGN KEY (id_residente) REFERENCES residentes(id_residente)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- ÍNDICES
-- ============================================================

CREATE INDEX idx_residentes_apellidos
    ON residentes(apellidos);

CREATE INDEX idx_residentes_estado
    ON residentes(estado);

CREATE INDEX idx_incidentes_fecha
    ON incidentes(fecha_hora);

CREATE INDEX idx_incidentes_residente
    ON incidentes(id_residente);

CREATE INDEX idx_actividades_fecha
    ON actividades(fecha);

CREATE INDEX idx_participaciones_residente
    ON participaciones(id_residente);

-- ============================================================
-- ROLES
-- ============================================================

INSERT INTO roles (nombre, descripcion) VALUES
('ADMINISTRADOR', 'Acceso completo al sistema'),
('SUPERVISOR', 'Supervisa residentes, actividades e incidentes'),
('PERSONAL', 'Gestiona las operaciones asignadas');

-- ============================================================
-- USUARIO INICIAL
-- Usuario: admin
-- Clave académica: Admin123*
--
-- Hash generado con password_hash('Admin123*', PASSWORD_DEFAULT)
-- ============================================================

INSERT INTO usuarios
(id_rol, username, password_hash, nombres, apellidos, email)
VALUES
(1, 'admin',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llCk1K2vP0g8QjQj4nQ3u',
 'Administrador', 'Sistema',
 'admin@residencia.local');

-- ============================================================
-- HABITACIONES
-- ============================================================

INSERT INTO habitaciones
(numero, piso, capacidad, estado, observaciones) VALUES
('101',1,2,'OCUPADA','Habitación doble'),
('102',1,2,'OCUPADA','Habitación doble'),
('103',1,1,'DISPONIBLE','Habitación individual'),
('104',1,2,'OCUPADA','Habitación doble'),
('105',1,1,'DISPONIBLE','Habitación individual'),
('201',2,2,'OCUPADA','Habitación doble'),
('202',2,2,'OCUPADA','Habitación doble'),
('203',2,1,'DISPONIBLE','Habitación individual'),
('204',2,2,'OCUPADA','Habitación doble'),
('205',2,1,'MANTENIMIENTO','Revisión de instalación eléctrica'),
('301',3,2,'OCUPADA','Habitación doble'),
('302',3,2,'DISPONIBLE','Habitación doble'),
('303',3,1,'OCUPADA','Habitación individual'),
('304',3,2,'DISPONIBLE','Habitación doble'),
('305',3,1,'OCUPADA','Habitación individual');

-- ============================================================
-- PERSONAL
-- ============================================================

INSERT INTO personal
(codigo_personal,nombres,apellidos,cargo,especialidad,telefono,email,fecha_ingreso) VALUES
('PER001','Ana','Torres Mendoza','Enfermera','Enfermería geriátrica','999100001','ana.torres@residencia.local','2024-02-12'),
('PER002','Luis','Ramírez Soto','Médico','Geriatría','999100002','luis.ramirez@residencia.local','2023-08-15'),
('PER003','María','Quispe Flores','Enfermera','Cuidados del adulto mayor','999100003','maria.quispe@residencia.local','2024-04-08'),
('PER004','Carlos','Vega Salazar','Fisioterapeuta','Rehabilitación','999100004','carlos.vega@residencia.local','2024-01-20'),
('PER005','Rosa','Paredes León','Nutricionista','Nutrición geriátrica','999100005','rosa.paredes@residencia.local','2023-11-06'),
('PER006','Jorge','Castillo Ruiz','Psicólogo','Psicología geriátrica','999100006','jorge.castillo@residencia.local','2024-03-11'),
('PER007','Elena','Vargas Peña','Enfermera','Enfermería','999100007','elena.vargas@residencia.local','2025-01-13'),
('PER008','Pedro','Huamán Díaz','Cuidador','Cuidados básicos','999100008','pedro.huaman@residencia.local','2025-02-03'),
('PER009','Claudia','Morales Ríos','Trabajadora Social','Trabajo social','999100009','claudia.morales@residencia.local','2024-06-17'),
('PER010','Miguel','Salinas Cruz','Cuidador','Cuidados básicos','999100010','miguel.salinas@residencia.local','2025-03-10'),
('PER011','Patricia','Gómez Luna','Enfermera','Enfermería geriátrica','999100011','patricia.gomez@residencia.local','2025-04-14'),
('PER012','Diego','Campos Silva','Fisioterapeuta','Rehabilitación','999100012','diego.campos@residencia.local','2025-05-05'),
('PER013','Lucía','Mendoza Paz','Psicóloga','Psicología','999100013','lucia.mendoza@residencia.local','2024-09-02'),
('PER014','Fernando','Rojas Núñez','Cuidador','Cuidados básicos','999100014','fernando.rojas@residencia.local','2025-06-02'),
('PER015','Gabriela','Vásquez Ortiz','Coordinadora','Gestión de residencia','999100015','gabriela.vasquez@residencia.local','2023-07-10');

-- ============================================================
-- RESIDENTES
-- Datos sintéticos. No corresponden a personas reales.
-- ============================================================

INSERT INTO residentes
(id_habitacion,codigo_residente,nombres,apellidos,sexo,fecha_nacimiento,estado_civil,
telefono,distrito,provincia,departamento,fecha_ingreso,contacto_emergencia,
telefono_emergencia,estado,observaciones) VALUES
(1,'RES001','Elena','Martínez Rojas','F','1941-03-15','VIUDO','999200001','Pueblo Libre','Lima','Lima','2025-01-10','Rosa Martínez','999300001','ACTIVO','Sin observaciones'),
(1,'RES002','José','Paredes Gómez','M','1938-07-21','CASADO','999200002','San Miguel','Lima','Lima','2025-01-12','Luis Paredes','999300002','ACTIVO','Control médico mensual'),
(2,'RES003','Carmen','Flores Díaz','F','1945-11-03','VIUDO','999200003','Magdalena','Lima','Lima','2025-02-03','Ana Flores','999300003','ACTIVO','Usa lentes'),
(2,'RES004','Manuel','Torres Vega','M','1940-01-28','CASADO','999200004','Pueblo Libre','Lima','Lima','2025-02-10','Carlos Torres','999300004','ACTIVO','Movilidad independiente'),
(4,'RES005','Rosa','Quispe León','F','1947-05-18','SOLTERO','999200005','Jesús María','Lima','Lima','2025-02-18','Marta Quispe','999300005','ACTIVO','Participa en talleres'),
(4,'RES006','Alberto','Ramírez Soto','M','1939-09-12','VIUDO','999200006','Breña','Lima','Lima','2025-03-01','Pedro Ramírez','999300006','ACTIVO','Requiere seguimiento'),
(6,'RES007','Julia','Mendoza Cruz','F','1944-02-25','CASADO','999200007','Lince','Lima','Lima','2025-03-15','Sonia Mendoza','999300007','ACTIVO','Sin observaciones'),
(6,'RES008','Ricardo','Vargas Peña','M','1942-10-30','DIVORCIADO','999200008','San Isidro','Lima','Lima','2025-03-20','Marco Vargas','999300008','ACTIVO','Usa bastón'),
(7,'RES009','Teresa','Castillo Ruiz','F','1948-06-09','VIUDO','999200009','Miraflores','Lima','Lima','2025-04-05','Elena Castillo','999300009','ACTIVO','Participa en fisioterapia'),
(7,'RES010','Andrés','Salazar Núñez','M','1937-12-17','CASADO','999200010','Pueblo Libre','Lima','Lima','2025-04-12','Laura Salazar','999300010','ACTIVO','Control nutricional'),
(9,'RES011','Beatriz','Huamán Díaz','F','1946-08-22','VIUDO','999200011','La Molina','Lima','Lima','2025-04-20','Rocío Huamán','999300011','ACTIVO','Sin observaciones'),
(9,'RES012','Francisco','Rojas Lima','M','1943-04-11','CASADO','999200012','Surco','Lima','Lima','2025-05-02','Miguel Rojas','999300012','ACTIVO','Diabetes controlada'),
(11,'RES013','Mercedes','Campos Silva','F','1940-12-05','VIUDO','999200013','San Borja','Lima','Lima','2025-05-14','Patricia Campos','999300013','ACTIVO','Requiere control periódico'),
(11,'RES014','Enrique','Gómez Luna','M','1939-03-27','CASADO','999200014','Pueblo Libre','Lima','Lima','2025-05-21','Javier Gómez','999300014','ACTIVO','Sin observaciones'),
(13,'RES015','Nora','Vásquez Ortiz','F','1949-01-19','SOLTERO','999200015','Jesús María','Lima','Lima','2025-06-03','Claudia Vásquez','999300015','ACTIVO','Participa en actividades'),
(13,'RES016','Héctor','Morales Ríos','M','1941-07-14','VIUDO','999200016','Lince','Lima','Lima','2025-06-10','Daniel Morales','999300016','ACTIVO','Usa bastón'),
(15,'RES017','Adela','Paz Mendoza','F','1947-10-02','CASADO','999200017','San Miguel','Lima','Lima','2025-06-18','Lucía Paz','999300017','ACTIVO','Sin observaciones'),
(15,'RES018','Víctor','Cruz Salinas','M','1945-02-16','DIVORCIADO','999200018','Magdalena','Lima','Lima','2025-06-25','Sergio Cruz','999300018','ACTIVO','Requiere acompañamiento'),
(NULL,'RES019','Isabel','Ríos Torres','F','1942-09-08','VIUDO','999200019','Pueblo Libre','Lima','Lima','2025-07-01','Ana Ríos','999300019','ACTIVO','Pendiente de asignación'),
(NULL,'RES020','Roberto','León Paredes','M','1946-05-29','CASADO','999200020','San Isidro','Lima','Lima','2025-07-07','Mario León','999300020','ACTIVO','Pendiente de asignación');

-- ============================================================
-- TIPOS DE INCIDENTE
-- ============================================================

INSERT INTO tipos_incidente (nombre,descripcion,nivel_riesgo) VALUES
('Caída','Caída accidental del residente','ALTO'),
('Desorientación','Episodio de desorientación o confusión','MEDIO'),
('Malestar físico','Malestar que requiere evaluación','MEDIO'),
('Golpe','Golpe o contusión accidental','MEDIO'),
('Corte','Herida o corte superficial','MEDIO'),
('Reacción alimentaria','Incidente relacionado con alimentos','ALTO'),
('Fuga o extravío','Salida no autorizada o pérdida temporal de ubicación','CRITICO'),
('Otro','Otro incidente operativo','BAJO');

-- ============================================================
-- ACTIVIDADES
-- ============================================================

INSERT INTO actividades
(id_personal_responsable,nombre,tipo,descripcion,fecha,hora_inicio,hora_fin,lugar,cupo,estado) VALUES
(4,'Gimnasia suave','Actividad física','Ejercicios de movilidad y estiramiento','2026-09-21','09:00:00','10:00:00','Sala de fisioterapia',12,'PROGRAMADA'),
(6,'Taller de memoria','Cognitiva','Ejercicios de memoria y atención','2026-09-21','10:30:00','11:30:00','Sala multiusos',15,'PROGRAMADA'),
(5,'Nutrición saludable','Educativa','Orientación sobre alimentación saludable','2026-09-22','10:00:00','11:00:00','Comedor',20,'PROGRAMADA'),
(4,'Caminata supervisada','Actividad física','Caminata de baja intensidad','2026-09-22','15:00:00','16:00:00','Jardín',10,'PROGRAMADA'),
(13,'Taller de música','Recreativa','Actividad musical participativa','2026-09-23','10:00:00','11:30:00','Sala multiusos',20,'PROGRAMADA'),
(6,'Conversatorio familiar','Social','Espacio de integración y conversación','2026-09-24','15:00:00','16:30:00','Sala social',25,'PROGRAMADA'),
(4,'Ejercicios de equilibrio','Prevención','Ejercicios para prevención de caídas','2026-09-25','09:00:00','10:00:00','Sala de fisioterapia',10,'PROGRAMADA'),
(5,'Taller de hidratación','Educativa','Importancia de la hidratación en adultos mayores','2026-09-25','11:00:00','12:00:00','Comedor',20,'PROGRAMADA'),
(13,'Lectura grupal','Cultural','Lectura y conversación sobre textos breves','2026-09-26','10:00:00','11:00:00','Biblioteca',15,'PROGRAMADA'),
(6,'Taller de bienestar emocional','Psicosocial','Actividades de expresión y bienestar emocional','2026-09-26','15:00:00','16:00:00','Sala social',15,'PROGRAMADA'),
(4,'Movilidad articular','Actividad física','Rutina de movilidad articular','2026-09-28','09:00:00','10:00:00','Sala de fisioterapia',12,'PROGRAMADA'),
(5,'Cocina saludable demostrativa','Educativa','Preparación demostrativa de alimentos saludables','2026-09-29','10:00:00','11:30:00','Cocina',10,'PROGRAMADA');

-- ============================================================
-- PARTICIPACIONES
-- ============================================================

INSERT INTO participaciones
(id_actividad,id_residente,asistencia,observaciones) VALUES
(1,1,'ASISTIO','Realizó la rutina completa'),
(1,3,'ASISTIO','Participación activa'),
(1,5,'ASISTIO','Sin observaciones'),
(1,7,'NO_ASISTIO','Reposo'),
(2,1,'ASISTIO','Buena participación'),
(2,9,'ASISTIO','Participación activa'),
(2,11,'ASISTIO','Sin observaciones'),
(2,15,'NO_ASISTIO','No disponible'),
(3,2,'ASISTIO','Participación activa'),
(3,5,'ASISTIO','Sin observaciones'),
(3,10,'ASISTIO','Realizó preguntas'),
(3,13,'ASISTIO','Sin observaciones'),
(4,4,'ASISTIO','Caminata supervisada'),
(4,8,'ASISTIO','Utilizó bastón'),
(4,16,'NO_ASISTIO','Pendiente de evaluación'),
(5,3,'ASISTIO','Participación activa'),
(5,7,'ASISTIO','Participación activa'),
(5,9,'ASISTIO','Sin observaciones'),
(5,15,'ASISTIO','Sin observaciones'),
(6,1,'ASISTIO','Participó con familiar'),
(6,6,'ASISTIO','Sin observaciones'),
(6,12,'NO_ASISTIO','No disponible'),
(7,8,'ASISTIO','Ejercicios adaptados'),
(7,16,'ASISTIO','Buena evolución'),
(7,17,'ASISTIO','Sin observaciones'),
(8,10,'ASISTIO','Participación activa'),
(8,13,'ASISTIO','Sin observaciones'),
(9,3,'ASISTIO','Lectura grupal'),
(9,11,'ASISTIO','Participación activa'),
(10,5,'ASISTIO','Participación activa'),
(10,15,'ASISTIO','Sin observaciones');

-- ============================================================
-- INCIDENTES
-- ============================================================

INSERT INTO incidentes
(id_residente,id_tipo_incidente,id_personal_reporta,fecha_hora,lugar,descripcion,
accion_realizada,requiere_seguimiento,estado,fecha_cierre) VALUES
(8,1,1,'2026-09-01 09:15:00','Pasillo del segundo piso',
'El residente presentó una caída al desplazarse hacia la sala común.',
'Evaluación inicial y comunicación al personal de enfermería.',
TRUE,'EN_SEGUIMIENTO',NULL),

(6,3,3,'2026-09-02 14:20:00','Habitación 102',
'El residente manifestó mareo leve.',
'Se realizó control de signos y reposo.',
TRUE,'CERRADO','2026-09-02 17:00:00'),

(3,2,6,'2026-09-03 10:40:00','Sala común',
'La residente presentó un episodio breve de desorientación.',
'Acompañamiento y comunicación con enfermería.',
TRUE,'CERRADO','2026-09-03 12:30:00'),

(12,4,7,'2026-09-04 16:10:00','Comedor',
'El residente reportó golpe leve en el brazo.',
'Aplicación de medidas básicas y observación.',
FALSE,'CERRADO','2026-09-04 18:00:00'),

(16,1,1,'2026-09-05 08:50:00','Jardín',
'El residente perdió momentáneamente el equilibrio durante una caminata.',
'Evaluación por fisioterapia y suspensión temporal de la actividad.',
TRUE,'EN_SEGUIMIENTO',NULL),

(5,3,3,'2026-09-06 11:25:00','Habitación 104',
'La residente reportó malestar físico leve.',
'Control de signos y descanso.',
FALSE,'CERRADO','2026-09-06 13:00:00'),

(10,5,7,'2026-09-07 15:30:00','Sala multiusos',
'Se produjo una pequeña herida superficial durante una actividad manual.',
'Limpieza y curación de la zona.',
FALSE,'CERRADO','2026-09-07 16:15:00'),

(2,3,'2026-09-08 09:10:00','Habitación 101',
'El residente presentó malestar general.',
'Evaluación por enfermería.',
TRUE,'EN_SEGUIMIENTO',NULL),

(9,4,1,'2026-09-09 12:45:00','Comedor',
'La residente reportó un golpe leve contra una silla.',
'Observación y aplicación de frío local.',
FALSE,'CERRADO','2026-09-09 14:00:00'),

(13,1,3,'2026-09-10 10:05:00','Pasillo del tercer piso',
'La residente presentó una caída sin pérdida de conciencia.',
'Evaluación inicial y derivación para valoración médica.',
TRUE,'EN_SEGUIMIENTO',NULL),

(18,2,6,'2026-09-11 17:20:00','Sala social',
'El residente presentó desorientación temporal.',
'Acompañamiento y comunicación con familiar.',
TRUE,'EN_SEGUIMIENTO',NULL),

(4,3,7,'2026-09-12 08:40:00','Habitación 102',
'El residente manifestó malestar físico.',
'Control de signos y observación.',
FALSE,'CERRADO','2026-09-12 10:30:00');

-- ============================================================
-- VISTA PARA CONSULTAS DE LOS ESTUDIANTES
-- ============================================================

CREATE VIEW vw_incidentes_residentes AS
SELECT
    i.id_incidente,
    r.codigo_residente,
    CONCAT(r.nombres, ' ', r.apellidos) AS residente,
    ti.nombre AS tipo_incidente,
    ti.nivel_riesgo,
    i.fecha_hora,
    i.lugar,
    i.estado,
    i.requiere_seguimiento
FROM incidentes i
INNER JOIN residentes r
    ON i.id_residente = r.id_residente
INNER JOIN tipos_incidente ti
    ON i.id_tipo_incidente = ti.id_tipo_incidente;

CREATE VIEW vw_participacion_actividades AS
SELECT
    p.id_participacion,
    a.nombre AS actividad,
    a.fecha,
    r.codigo_residente,
    CONCAT(r.nombres, ' ', r.apellidos) AS residente,
    p.asistencia,
    p.observaciones
FROM participaciones p
INNER JOIN actividades a
    ON p.id_actividad = a.id_actividad
INNER JOIN residentes r
    ON p.id_residente = r.id_residente;

-- ============================================================
-- FIN
-- ============================================================
