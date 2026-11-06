-- Base destino independiente. No elimina bases ni tablas existentes.
IF DB_ID(N'residencia_sqlserver') IS NULL CREATE DATABASE residencia_sqlserver;
GO
USE residencia_sqlserver;
GO
IF OBJECT_ID(N'dbo.personal', N'U') IS NULL
CREATE TABLE dbo.personal (
    id_personal INT NOT NULL PRIMARY KEY,
    codigo_personal NVARCHAR(20) NOT NULL UNIQUE,
    nombres NVARCHAR(100) NOT NULL,
    apellidos NVARCHAR(100) NOT NULL,
    cargo NVARCHAR(80) NOT NULL,
    especialidad NVARCHAR(100) NULL,
    telefono NVARCHAR(20) NULL,
    email NVARCHAR(120) NULL,
    fecha_ingreso DATE NOT NULL,
    estado NVARCHAR(20) NOT NULL DEFAULT 'ACTIVO' CHECK (estado IN ('ACTIVO','INACTIVO')),
    fecha_transferencia DATETIME2 NOT NULL DEFAULT SYSUTCDATETIME()
);
GO
IF OBJECT_ID(N'dbo.transferencias_personal', N'U') IS NULL
CREATE TABLE dbo.transferencias_personal (
    id INT NOT NULL PRIMARY KEY CHECK(id=1),
    transferido_el DATETIME2 NOT NULL,
    leidos INT NOT NULL,
    insertados INT NOT NULL,
    actualizados INT NOT NULL
);
GO
-- El administrador crea el login residencia_sql con una clave privada.
-- Tras crear el login, ejecutar:
-- CREATE USER residencia_sql FOR LOGIN residencia_sql;
-- GRANT SELECT, INSERT, UPDATE ON dbo.personal TO residencia_sql;
-- GRANT SELECT, INSERT, UPDATE ON dbo.transferencias_personal TO residencia_sql;
