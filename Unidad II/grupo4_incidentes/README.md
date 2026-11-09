# Módulo de incidentes

Aplicación Flask para administrar incidentes de residentes y transferir los registros de MySQL a SQL Server.

## Requisitos

- Python 3.10 o posterior.
- MySQL con la base `sistema_residencia` y las tablas `incidentes`, `residentes`, `tipos_incidente` y `personal`.
- SQL Server y el controlador ODBC configurado si se usará la transferencia.

## Instalación

Desde esta carpeta, crea un entorno virtual, instala las dependencias y copia `.env.example` como `.env`. Completa en `.env` las credenciales de las bases de datos disponibles.

```powershell
py -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install -r requirements.txt
Copy-Item .env.example .env
python app.py
```

La aplicación queda disponible en `http://localhost:5000`. La interfaz inicial está en `/` y la verificación del servicio en `/api/health`.

## Funcionalidades

- Interfaz web para crear, consultar, editar y eliminar incidentes.
- API REST bajo `/api/incidentes`, con listados, filtros, indicadores y catálogos.
- Transferencia de registros de MySQL a SQL Server y resumen de la transferencia.
- Colección de solicitudes para Postman en `postman/coleccion.json`.

El esquema auxiliar de SQL Server está en `sql/sqlserver_incidentes.sql`. La base MySQL y sus catálogos deben estar creados antes de iniciar la aplicación.

## Configuración

`config.py` carga la configuración desde variables de entorno. `.env.example` contiene valores de ejemplo; reemplázalos localmente y no publiques credenciales reales.
