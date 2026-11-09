-- ============================================================
--  Grupo 6 - Modulo Actividades
--  Tabla equivalente en SQL Server (base residencia_sqlserver)
--  Destino de la transferencia desde MySQL.
--
--  Reglas (plan Unidad II):
--   - Mismas columnas que en MySQL + fecha_transferencia.
--   - La llave primaria se copia tal cual: SIN IDENTITY.
--   - Sin llaves foraneas en el destino.
--   - Los ENUM de MySQL se vuelven NVARCHAR con CHECK.
-- ============================================================

-- Ejecutar una sola vez (crea la base si no existe):
-- IF DB_ID('residencia_sqlserver') IS NULL CREATE DATABASE residencia_sqlserver;
-- GO
USE residencia_sqlserver;
GO

IF OBJECT_ID('dbo.actividades', 'U') IS NOT NULL
    DROP TABLE dbo.actividades;
GO

CREATE TABLE dbo.actividades (
    id_actividad        INT           NOT NULL PRIMARY KEY,   -- sin IDENTITY
    id_personal         INT           NULL,                   -- responsable
    nombre              NVARCHAR(100) NOT NULL,
    descripcion         NVARCHAR(255) NULL,
    fecha               DATE          NOT NULL,
    hora_inicio         TIME(0)       NOT NULL,
    hora_fin            TIME(0)       NOT NULL,
    lugar               NVARCHAR(80)  NOT NULL,
    cupo                INT           NOT NULL CHECK (cupo > 0),
    estado              NVARCHAR(20)  NOT NULL DEFAULT 'PROGRAMADA'
        CHECK (estado IN ('PROGRAMADA','REALIZADA','CANCELADA')),
    fecha_transferencia DATETIME2     NOT NULL DEFAULT SYSDATETIME(),
    CONSTRAINT chk_actividades_horas CHECK (hora_fin > hora_inicio)
);
GO

-- ============================================================
--  Usuario propio con permisos SOLO sobre esta base (no usar sa).
--  Ejecutar como administrador una sola vez:
-- ============================================================
-- USE master;
-- GO
-- CREATE LOGIN residencia_sql WITH PASSWORD = 'CambiarEstaClave_2026';
-- GO
-- USE residencia_sqlserver;
-- GO
-- CREATE USER residencia_sql FOR LOGIN residencia_sql;
-- ALTER ROLE db_datareader  ADD MEMBER residencia_sql;
-- ALTER ROLE db_datawriter  ADD MEMBER residencia_sql;
-- GO
