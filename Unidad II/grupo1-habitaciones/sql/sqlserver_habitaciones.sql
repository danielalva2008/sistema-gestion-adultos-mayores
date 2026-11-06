-- Grupo 1 / Integrante 4: tabla destino de la transferencia
-- La base residencia_sqlserver debe existir previamente.
USE residencia_sqlserver;
GO

IF OBJECT_ID(N'dbo.habitaciones', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.habitaciones (
        id_habitacion       INT           NOT NULL PRIMARY KEY, -- sin IDENTITY
        numero              NVARCHAR(10)  NOT NULL UNIQUE,
        piso                INT           NOT NULL CHECK (piso > 0),
        capacidad           INT           NOT NULL CHECK (capacidad > 0),
        estado              NVARCHAR(20)  NOT NULL DEFAULT 'DISPONIBLE'
            CHECK (estado IN ('DISPONIBLE','OCUPADA','MANTENIMIENTO')),
        observaciones       NVARCHAR(255) NULL,
        fecha_transferencia DATETIME2     NOT NULL DEFAULT SYSDATETIME()
    );
END;
GO

-- Solo para reiniciar pruebas, nunca ejecutar sobre datos que deban conservarse:
-- TRUNCATE TABLE dbo.habitaciones;
