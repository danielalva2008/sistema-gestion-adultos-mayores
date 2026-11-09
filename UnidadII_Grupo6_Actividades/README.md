# Grupo 6 · Módulo Actividades — Unidad II

Proyecto integrador del curso **Desarrollo de Aplicaciones Basado en Plataformas**.
Backend **Flask (Python)** que expone en JSON los KPIs, la gráfica comparativa, el
CRUD y el listado con filtros de la tabla **`actividades`**, más la **transferencia
de MySQL a SQL Server**. El front usa la plantilla **AdminLTE / Bootstrap 5**.

> Módulo del Grupo 6: tabla `actividades` (apoyo: `participaciones`, `personal`,
> vista `vw_participacion_actividades`). Eje: participación y asistencia.

---

## 0. Estado de configuración (verificado 2026-11-06)

El entorno quedó **configurado y probado end-to-end** en esta máquina:

- **MySQL (XAMPP / MariaDB 10.4)**: iniciado. Se reparó la tabla de privilegios
  `mysql.db` que estaba corrupta e impedía arrancar (`REPAIR TABLE mysql.db`).
  Base `sistema_residencia` con 16 actividades y 33 participaciones.
- **SQL Server 2025**: base `residencia_sqlserver` y tabla `dbo.actividades`
  creadas. Conexión por **autenticación de Windows** (`SQLSERVER_TRUSTED=1` en
  `.env`), ya que el puerto TCP 1433 no está habilitado en esta instancia.
- **Dependencias**: Flask, PyMySQL, python-dotenv, flask-cors y pyodbc 5.4
  instaladas.
- **Transferencia probada**: 16 insertados la 1ª vez, 16 actualizados / 0
  insertados la 2ª vez (idempotente, sin duplicados). Ver
  `postman/evidencia_pruebas.txt`.

> Para usar la autenticación SQL del plan (usuario `residencia_sql` por TCP
> 1433) hay que habilitar TCP/IP en SQL Server Configuration Manager, activar el
> modo de autenticación mixta, crear el login y poner `SQLSERVER_TRUSTED=0`.

## 1. Requisitos

- Python 3.10+
- MySQL 8 (XAMPP) con la base `sistema_residencia` cargada (`database.sql`).
- SQL Server Express/Developer + **ODBC Driver 17/18 for SQL Server**, TCP/IP en el
  puerto 1433 y la base `residencia_sqlserver` creada.
- Usuario de MySQL `residencia_app` (solo SELECT/INSERT/UPDATE/DELETE, **no root**).

## 2. Instalación

```bash
cd UnidadII_Grupo6_Actividades
python -m venv venv
venv\Scripts\activate            # Windows
pip install -r requirements.txt
copy .env.example .env           # y completar credenciales
```

## 3. Base de datos destino (SQL Server)

Ejecutar en SSMS el script `sql/sqlserver_actividades.sql` (crea
`dbo.actividades` y, comentado, el usuario `residencia_sql` con permisos solo
sobre esa base).

## 4. Ejecutar

```bash
python app.py
```

Abrir **http://127.0.0.1:5000/**

- `/`         → Dashboard (KPIs, gráfica y botón Transferir)
- `/listado`  → Listado con filtros (`tables/data.html`)
- `/crud`     → Mantenimiento (crear / editar)

## 5. Endpoints de la API

| Método | Ruta | Descripción | Códigos |
|--------|------|-------------|---------|
| GET | `/api/actividades/kpis` | KPIs del dashboard (acepta `fecha_desde`, `fecha_hasta`) | 200 · 400 |
| GET | `/api/actividades/grafico` | Series de la gráfica (cupo vs inscritos) | 200 |
| GET | `/api/actividades` | Listado con filtros | 200 · 400 |
| GET | `/api/actividades/<id>` | Un registro | 200 · 404 |
| POST | `/api/actividades` | Crear | 201 · 400 · 409 |
| PUT | `/api/actividades/<id>` | Actualizar | 200 · 400 · 404 · 409 |
| DELETE | `/api/actividades/<id>` | Eliminar (cascada de participaciones) | 200 · 404 |
| GET | `/api/actividades/catalogos` | Personal responsable y lugares | 200 |
| POST | `/api/actividades/transferir` | Copiar a SQL Server (MERGE) | 200 · 500 · 503 |
| GET | `/api/actividades/transferencia/resumen` | Conteos MySQL / SQL Server | 200 · 503 |
| POST | `/api/actividades/<id>/participaciones` | *(Opcional, puntaje extra)* Inscribir | 201 · 400 · 404 · 409 |

Formato de respuesta: `{ "ok": true, "total": N, "data": [...] }` o
`{ "ok": false, "error": "..." }`.

## 6. KPIs (calculados en SQL)

1. **Actividades programadas** — `COUNT(*)` con `estado='PROGRAMADA'` en el rango.
2. **Total de participaciones** — `COUNT(*)` de participaciones de las actividades del rango.
3. **Tasa de asistencia (%)** — `ASISTIO ÷ (ASISTIO + NO_ASISTIO) × 100` (los `INSCRITO` no cuentan).
4. **Ocupación de cupo (%)** — `participaciones ÷ SUM(cupo) × 100`.
5. **Cobertura de residentes (%)** — residentes distintos que participan ÷ residentes `ACTIVO` × 100.
6. **Participantes por actividad** — `participaciones ÷ actividades`.

Toda razón usa `NULLIF(...,0)` para evitar la división entre cero.

## 7. Transferencia MySQL → SQL Server

`POST /api/actividades/transferir` lee todas las actividades de MySQL, abre una
transacción en SQL Server y aplica un **MERGE** por `id_actividad` (actualiza si
existe, inserta si no). Es **idempotente**: ejecutarla varias veces no duplica
datos. Responde con `leidos`, `insertados`, `actualizados`, `duracion_ms` y
`transferido_el`.

## 8. Pruebas y evidencia

- Colección Postman: `postman/coleccion.json` (variable `base_url`).
- Comandos curl: `postman/curl_pruebas.txt`.
- Resultados verificados: `postman/evidencia_pruebas.txt`.
- Capturas (carpeta `capturas/`):
  - `1_dashboard.png` — KPIs, gráfica comparativa y resumen de transferencia.
  - `2_listado.png` — listado con filtros y acciones.
  - `3_crud.png` — formulario de mantenimiento (editar).
  - `4_transferencia.png` — resultado de la transferencia a SQL Server.

## 9. Filtros del listado (mínimo 3 + fecha)

Estado · Lugar · Responsable · Búsqueda (`q`) · Rango de fechas (`fecha_desde`/`fecha_hasta`).

> **Nota de adaptación al esquema real.** El esquema `sistema_residencia` de la
> Unidad I no tiene columna `tipo` en `actividades` y usa `id_personal` como
> responsable (en vez de `id_personal_responsable` del plan genérico). Este
> proyecto se construyó contra el esquema real; por eso el filtro `tipo` se
> sustituye por `lugar`/`responsable`, manteniendo el mínimo de filtros exigido.

## 10. Seguridad

- Credenciales en `.env` (no se versiona; se entrega `.env.example`).
- Usuario MySQL `residencia_app`, no root; usuario SQL Server propio, no `sa`.
- Consultas parametrizadas (`%s` en MySQL, `?` en pyodbc).
- No se registran datos personales en logs (Ley N.° 29733).

## 11. Integrantes del Grupo 6

| Rol | Integrante |
|-----|-----------|
| Líder y base de datos | Abarca Meléndez Luis Marcelo |
| Backend de KPIs | Vargas Córdova Diego Arnold |
| Backend de CRUD y transferencia | López Tuesta Julio César |
| Frontend del dashboard | Farroñán Quiñonez Alex Josué |
| Frontend de listado y pruebas | Meza Villacorta Jhohan Lizandro |
