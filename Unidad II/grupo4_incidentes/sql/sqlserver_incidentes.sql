USE residencia_sqlserver;
GO

CREATE TABLE dbo.incidentes (
    id_incidente         INT           NOT NULL PRIMARY KEY,
    id_residente         INT           NOT NULL,
    id_tipo_incidente    INT           NOT NULL,
    id_personal_reporta  INT           NULL,
    fecha_hora           DATETIME2     NOT NULL,
    lugar                NVARCHAR(150) NOT NULL,
    descripcion          NVARCHAR(MAX) NOT NULL,
    accion_realizada     NVARCHAR(MAX) NULL,
    requiere_seguimiento BIT           NOT NULL DEFAULT 0,
    estado               NVARCHAR(20)  NOT NULL DEFAULT 'REGISTRADO'
        CHECK (estado IN ('REGISTRADO','EN_SEGUIMIENTO','CERRADO')),
    fecha_cierre         DATETIME2     NULL,
    fecha_transferencia  DATETIME2     NOT NULL DEFAULT SYSDATETIME()
);
GO