-- =====================================================================
-- TABLA DESTINO DEL GRUPO 3
-- La base se crea solo si no existe. personal conserva los IDs de MySQL,
-- no usa IDENTITY ni llaves foráneas y añade fecha_transferencia.
-- Las restricciones validan código único y los dos estados permitidos.
-- =====================================================================
-- Ejecutar con un administrador. No borra tablas ni datos existentes.
IF DB_ID(N'residencia_sqlserver') IS NULL
    CREATE DATABASE residencia_sqlserver;
GO
USE residencia_sqlserver;
GO
IF OBJECT_ID(N'dbo.personal', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.personal (
        id_personal INT NOT NULL PRIMARY KEY, -- Se copia la PK; no IDENTITY.
        codigo_personal NVARCHAR(20) NOT NULL UNIQUE,
        nombres NVARCHAR(100) NOT NULL,
        apellidos NVARCHAR(100) NOT NULL,
        cargo NVARCHAR(80) NOT NULL,
        especialidad NVARCHAR(100) NULL,
        telefono NVARCHAR(20) NULL,
        email NVARCHAR(120) NULL,
        fecha_ingreso DATE NOT NULL,
        estado NVARCHAR(20) NOT NULL DEFAULT 'ACTIVO'
            CHECK (estado IN ('ACTIVO','INACTIVO')),
        fecha_transferencia DATETIME2 NOT NULL DEFAULT SYSDATETIME()
    );
END;
GO
-- El administrador debe crear residencia_sql y conceder únicamente:
-- GRANT SELECT, INSERT, UPDATE ON dbo.personal TO residencia_sql;
-- No se copian llaves foráneas ni se borran registros de destino.
