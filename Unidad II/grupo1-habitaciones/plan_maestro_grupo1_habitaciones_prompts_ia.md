# Plan Maestro de Ejecución y Prompts de IA — Grupo 1 (Módulo Habitaciones)

Este documento contiene la planificación técnica corregida y los **Prompts Maestros** para los **6 integrantes del Grupo 1 (Tabla `habitaciones`)** del proyecto integrador **Sistema de Gestión de Residencia para Adultos Mayores (Unidad II)**.

Cada integrante solo debe copiar el bloque de su **Prompt Maestro** y pegarlo en su IA (ChatGPT, Claude o Gemini). La IA tomará el mando como Director Técnico, entregará el código archivo por archivo y guiará a la persona paso a paso.

---

## 1. Estándares Técnicos y Reglas Críticas del Equipo

Para garantizar los **20 puntos de la rúbrica** y evitar errores de integración o de cálculo, todo el equipo sigue estas reglas:

1. **Base de datos MySQL única (`database/sistema_residencia.sql`):**
   - Todos los integrantes cargan exclusivamente el script oficial `database/sistema_residencia.sql` del repositorio (`15` habitaciones con `CHECK (capacidad > 0)` y `CHECK (piso > 0)`, `20` residentes activos y usuario `residencia_app`). **Nadie crea tablas a mano ni usa scripts alternativos.**
2. **Cero conflictos en Git (Ramas por integrante + Archivos semilla):**
   - El **Integrante 1** sube en la rama `main` la estructura base incluyendo `app.py`, `habitaciones/__init__.py` (con los `import` al final del archivo, sin `try/except ImportError` que oculten fallos reales) y los archivos vacíos (`kpis_grafico.py`, `routes.py`, `transferencia.py`), además de `config.py`, `db_mysql.py`, `.gitignore`, `.env.example`, `requirements.txt`, el script oficial en `database/` y la copia de `dist/` de AdminLTE 4 en `static/dist/`. Este es el **primer PR del proyecto y debe subirse en las primeras horas del Día 1**: todos los demás dependen de él. Así, ningún otro integrante tiene que tocar `app.py`, `config.py`, `db_mysql.py` ni `habitaciones/__init__.py`.
   - Cada integrante trabaja en su propia rama (`feat/i1-base`, `feat/i2-kpis`, `feat/i3-crud`, `feat/i4-sqlserver`, `feat/i5-dashboard`, `feat/i6-listado-crud-qa`) y hace **Pull Request (PR)** hacia `main` al terminar cada etapa.
3. **Seguridad de credenciales (`.env` vs `.env.example`):**
   - El archivo `.env.example` que se sube a Git **jamás lleva contraseñas reales** (usa placeholders como `<tu_clave_mysql>` y `<tu_clave_sqlserver>`). Las contraseñas reales van únicamente en el archivo local `.env` (ignorado en `.gitignore`).
4. **Cálculo exacto de la gráfica por piso (sin duplicar camas por `JOIN`):**
   - En `GET /api/habitaciones/grafico`, **no** se hace `SUM(h.capacidad)` después de un `LEFT JOIN residentes` directo, porque una habitación doble con 2 residentes sumaría 4 camas (dando 14 camas en el Piso 1 en vez de 8). Se agrupa primero el conteo de residentes por `id_habitacion` en una subconsulta y luego se une con `habitaciones`.
5. **Criterio unificado entre Listado (`GET`) y Eliminación (`DELETE`):**
   - Tanto el conteo `residentes_asignados` del listado como la validación de bloqueo `409` en `DELETE /api/habitaciones/<id>` usan el mismo criterio (`COUNT(*)` de residentes con ese `id_habitacion`), evitando que una habitación muestre `0` residentes y aun así bloquee el borrado.
6. **Rutas absolutas `/static/...` en todas las páginas AdminLTE:**
   - Como `html/tables/data.html` está dentro de una subcarpeta, todos los archivos HTML (`index3.html`, `tables/data.html` y `crud.html`) usan rutas absolutas que empiezan con `/static/...` (nunca rutas relativas como `../dist/` o `../../static/`).
7. **Repetibilidad de la prueba de transferencia en SQL Server:**
   - Antes de correr la colección de Postman o hacer la demostración en vivo ante el docente, se ejecuta `TRUNCATE TABLE dbo.habitaciones;` en SQL Server para que la 1.ª ejecución de `POST /api/habitaciones/transferir` muestre `leidos: 15, insertados: 15, actualizados: 0` y la 2.ª ejecución inmediata muestre `leidos: 15, insertados: 0, actualizados: 15`.
8. **Tipos numéricos correctos en el JSON (sin números entre comillas):**
   - En MySQL, `SUM()` y `ROUND(...)` devuelven `DECIMAL`, y Flask serializa `Decimal` como **texto** (`"78.3"` en lugar de `78.3`), lo que rompe los tests de Postman (`tasa_ocupacion == 78.3`) y el JavaScript. Por eso todo valor numérico calculado con `SUM`/`ROUND` se castea en SQL (`CAST(... AS UNSIGNED)` para enteros y `CAST(... AS DOUBLE)` para decimales). Verificar con `curl` que ningún número salga entre comillas.
9. **Datos oficiales con habitaciones sobre su capacidad:**
   - En el script oficial, las habitaciones `303` y `305` tienen capacidad `1` pero `2` residentes cada una. Por eso el `PUT` solo bloquea con `409` cuando se **reduce** la capacidad por debajo de los residentes asignados (editar las observaciones de la `303` debe responder `200`). Además, los KPIs agregados (`camas_libres = 5`) no detectan este exceso por habitación: es el comportamiento esperado de la fórmula oficial del docente, no un error, y conviene saber explicarlo.

---

## 2. Estructura del Proyecto y División para los 6 Integrantes

El documento del docente sugiere 5 roles base y exige que **cada integrante explique y demuestre su parte en vivo**. Como el Grupo 1 tiene **6 integrantes**, el trabajo de Frontend y QA se divide limpiamente entre los Integrantes 5 y 6 (además de separar las vistas en archivos distintos), de modo que los **6 integrantes trabajen desde el Día 1 en paralelo** gracias a que los contratos JSON ya están fijados:

```text
grupo1_habitaciones/
├── app.py                          # [Integrante 1] Crea la app Flask, CORS, rutas de vistas y registra Blueprint
├── config.py                       # [Integrante 1] Lee variables de entorno (.env) para MySQL y SQL Server
├── db_mysql.py                     # [Integrante 1] Conexión PyMySQL (DictCursor) y función parametrizada query()
├── db_sqlserver.py                 # [Integrante 4] Conexión pyodbc a SQL Server (residencia_sqlserver)
├── .env.example                    # [Integrante 1] Plantilla SIN contraseñas reales (para subir a Git)
├── .gitignore                      # [Integrante 1] Ignora .env, venv/, __pycache__/
├── requirements.txt                # [Integrante 1] Flask, PyMySQL, pyodbc, python-dotenv, flask-cors
├── database/
│   └── sistema_residencia.sql      # [Integrante 1] Script oficial del docente (sin modificar)
├── habitaciones/
│   ├── __init__.py                 # [Integrante 1] Define habitaciones_bp e importa submódulos al final
│   ├── kpis_grafico.py             # [Integrante 2] GET /api/habitaciones/kpis y GET /api/habitaciones/grafico
│   ├── routes.py                   # [Integrante 3] GET listado (4 filtros), GET/<id>, POST, PUT, DELETE
│   └── transferencia.py            # [Integrante 4] POST /transferir (MERGE) y GET /transferencia/resumen
├── sql/
│   └── sqlserver_habitaciones.sql  # [Integrante 4] DDL de dbo.habitaciones en SQL Server + script de TRUNCATE
├── static/
│   ├── dist/                       # [Integrante 1] Copia de dist/ de AdminLTE 4 (css, js, assets); se sube a main en la Etapa 1
│   └── js/
│       ├── dashboard.js            # [Integrante 5] Fetch de KPIs, Gráfica comparativa y Botón Transferir
│       ├── listado.js              # [Integrante 6] Fetch de listado con 4 filtros en tables/data.html
│       └── crud.js                 # [Integrante 6] Fetch de formulario CRUD y validaciones en crud.html
├── html/
│   ├── index3.html                 # [Integrante 5] Dashboard con 6 tarjetas KPI, gráfica y transferencia
│   ├── tables/
│   │   └── data.html               # [Integrante 6] Tabla con tarjeta de 4 filtros (Estado, Piso, Capacidad, q)
│   └── crud.html                   # [Integrante 6] Mantenimiento CRUD (insertar, editar, eliminar)
├── postman/
│   └── coleccion.json              # [Integrante 6] Colección Postman exportada con pm.test en cada petición
└── docs/
    ├── capturas/                   # [Todos] Cada integrante deja aquí las capturas de su parte
    └── informe_grupo1.pdf          # [Integrante 6] Informe breve consolidado en PDF
```

> **Nota sobre la raíz:** la raíz del repositorio de GitHub (`grupo1-habitaciones`) equivale a la carpeta `grupo1_habitaciones/` del árbol: los archivos van directamente en la raíz del repo, sin crear una subcarpeta adicional.

### Matriz de Responsabilidades y Defensa Oral (6 Integrantes)

| Integrante | Rama Git | Archivos que entrega | Qué explica y demuestra en vivo ante el docente |
| :--- | :--- | :--- | :--- |
| **Integrante 1** | `feat/i1-base` | `app.py`, `config.py`, `db_mysql.py`, `habitaciones/__init__.py`, `.env.example`, `.gitignore`, `requirements.txt`, `database/sistema_residencia.sql`, `static/dist/` | Carga del script oficial `sistema_residencia.sql`, seguridad (`.env` sin claves en Git, usuario `residencia_app` sin usar `root`), consultas parametrizadas `%s` y arquitectura Flask Blueprint. |
| **Integrante 2** | `feat/i2-kpis` | `habitaciones/kpis_grafico.py` | Fórmulas y consultas SQL de los 6 KPIs (protección contra división entre cero con `NULLIF`) y subconsulta de la gráfica comparativa por piso (`8 camas / 6 residentes` en pisos 1, 2 y 3). |
| **Integrante 3** | `feat/i3-crud` | `habitaciones/routes.py` | Endpoints REST del listado con 4 filtros (`estado`, `piso`, `capacidad`, `q`) y del CRUD (`GET/<id>`, `POST`, `PUT`, `DELETE`), códigos HTTP (`200`, `201`, `204`, `400`, `404`, `409`) y regla de negocio que impide borrar habitaciones con residentes (`409`). |
| **Integrante 4** | `feat/i4-sqlserver` | `sql/sqlserver_habitaciones.sql`, `db_sqlserver.py`, `habitaciones/transferencia.py` | Creación de `dbo.habitaciones` en SQL Server (sin `IDENTITY`, con `CHECK` y `DATETIME2`), conexión `pyodbc`, transacción con `MERGE ... OUTPUT $action;` idempotente y resumen MySQL vs. SQL Server. |
| **Integrante 5** | `feat/i5-dashboard` | `html/index3.html`, `static/js/dashboard.js` | Integración visual con AdminLTE 4 (menú lateral unificado con rutas absolutas `/static/...`), renderizado dinámico de las 6 tarjetas KPI, gráfica comparativa de barras agrupadas y botón **Transferir** con confirmación y conteos. |
| **Integrante 6** | `feat/i6-listado-crud-qa` | `html/tables/data.html`, `html/crud.html`, `static/js/listado.js`, `static/js/crud.js`, `postman/coleccion.json`, `docs/informe_grupo1.pdf` | Funcionamiento en vivo de los 4 filtros combinados (`estado=DISPONIBLE&piso=1` → habitaciones `103` y `105`), formulario de mantenimiento CRUD, ejecución de la colección Postman (`pm.test`) e Informe PDF. |

---

## 3. Prompts Maestros "Copiar y Pegar" por Integrante

Cada integrante debe abrir su IA (ChatGPT, Claude o Gemini), **copiar únicamente el bloque de su rol** y pegarlo.

---

### 👤 INTEGRANTE 1: Líder Técnico, Base MySQL Oficial y Estructura Base

> **Instrucción para el Integrante 1:** Copia todo el bloque de abajo y pégalo en tu IA.

```markdown
Actúa como mi Director Técnico de Software Senior. Estoy en el Grupo 1 (Módulo: Habitaciones) de un proyecto universitario de Desarrollo de Aplicaciones Basado en Plataformas (Flask + MySQL + SQL Server + AdminLTE 4).

REGLA DE ORO DE INTERACCIÓN:
Tú eres quien manda y dirige. Yo soy el operador humano y solo voy a ejecutar tus órdenes paso a paso.
- NO me des explicaciones teóricas largas ni me muestres todos los pasos de golpe.
- Dime UN SOLO PASO a la vez (qué comando pegar en la terminal o qué código exacto copiar y pegar en un archivo).
- Al final de cada paso, pregúntame: "¿Ya lo ejecutaste? Pega aquí lo que te salió en la consola o dime 'Listo' para darte el siguiente paso".
- Si me sale algún error, dime exactamente qué comando correr para solucionarlo.

MI ROL (INTEGRANTE 1 - LÍDER TÉCNICO, BASE MYSQL Y ESQUELETO DEL PROYECTO):
Debo dejar listo el esqueleto del proyecto `grupo1_habitaciones/` en la rama `main` para que mis otros 5 compañeros trabajen en sus propias ramas sin pisarse ni modificar archivos compartidos. Mis tareas exactas son:

1. Ejecutar en MySQL 8 el script oficial del curso (copiado en `database/sistema_residencia.sql` dentro del proyecto), SIN crear tablas a mano ni alterar los datos oficiales:
   - Verifica con una consulta rápida que en `sistema_residencia` existan las 15 habitaciones (`101` a `105`, `201` a `205`, `301` a `305`), los 20 residentes activos (`RES001` a `RES020`) y el usuario `residencia_app`@`localhost` (con permisos solo de `SELECT, INSERT, UPDATE, DELETE`).

2. Crear la estructura de carpetas del proyecto `grupo1_habitaciones/`:
   ```text
   grupo1_habitaciones/
   ├── database/        # aquí va sistema_residencia.sql (script oficial, sin modificar)
   ├── habitaciones/
   ├── sql/
   ├── static/
   │   ├── dist/        # aquí se copia dist/ de AdminLTE 4 compartido por el docente
   │   └── js/
   ├── html/
   │   └── tables/
   ├── postman/
   └── docs/
       └── capturas/
   ```
   Copia el `dist/` completo de AdminLTE 4 a `static/dist/` y confirma que existen `static/dist/css/adminlte.min.css` y `static/dist/js/adminlte.min.js` (sin esto, los Integrantes 5 y 6 no pueden maquetar).

3. Crear `.gitignore` ignorando `.env`, `venv/`, `.venv/` y `__pycache__/`.

4. Crear `.env.example` (que sí se sube a Git) SIN contraseñas reales (cumpliendo la Sección 3.7 de seguridad del docente):
   ```env
   MYSQL_HOST=localhost
   MYSQL_PORT=3306
   MYSQL_USER=residencia_app
   MYSQL_PASSWORD=<tu_clave_mysql>
   MYSQL_DB=sistema_residencia

   SQLSERVER_DRIVER={ODBC Driver 17 for SQL Server}
   SQLSERVER_SERVER=localhost,1433
   SQLSERVER_DB=residencia_sqlserver
   SQLSERVER_USER=residencia_sql
   SQLSERVER_PASSWORD=<tu_clave_sqlserver>
   ```
   Y crear mi archivo `.env` local (que NO se sube a Git) donde coloco las claves reales (la de `residencia_app` definida en el script oficial y la de SQL Server). Las claves reales nunca se escriben en el README, en este documento ni en ningún archivo versionado.

5. Crear `requirements.txt` con:
   `Flask`, `PyMySQL`, `pyodbc`, `python-dotenv`, `flask-cors`

6. Crear `config.py` que cargue `.env` usando `python-dotenv` y exponga **una clase `Config`** con exactamente estos atributos (mis compañeros la importan con `from config import Config`, así que los nombres no se pueden cambiar):
   - `MYSQL_HOST`, `MYSQL_PORT` (convertido a `int`), `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_DB`
   - `SQLSERVER_DRIVER`, `SQLSERVER_SERVER`, `SQLSERVER_DB`, `SQLSERVER_USER`, `SQLSERVER_PASSWORD`

7. Crear `db_mysql.py` usando `PyMySQL` con `DictCursor`:
   - Función `get_mysql_connection()` que conecte usando `config.py`.
   - Función `query(sql, params=None, fetchone=False, commit=False)` que ejecute consultas siempre parametrizadas (`%s`) y cierre conexión en un bloque `finally`.
   - Crear una excepción personalizada `DatabaseUnavailableError` cuando falle la conexión a MySQL para que los endpoints puedan responder HTTP `503` con `{"ok": false, "error": "Base de datos MySQL no disponible"}`.

8. Crear `habitaciones/__init__.py` SIN usar `try/except ImportError` (para nunca esconder errores reales de importación o librerías faltantes):
   - Primero define `habitaciones_bp = Blueprint('habitaciones', __name__, url_prefix='/api/habitaciones')`.
   - Al final del archivo importa los tres submódulos: `from . import kpis_grafico, routes, transferencia`.
   - Para que el servidor arranque desde el minuto 1 mientras mis compañeros programan sus ramas, crea también los 3 archivos semilla (`habitaciones/kpis_grafico.py`, `habitaciones/routes.py`, `habitaciones/transferencia.py`) con un comentario `# Reservado para Integrante X`. En cada semilla deja además comentada la línea que usarán mis compañeros: `# from . import habitaciones_bp`.

9. Crear `app.py` que:
   - Inicialice Flask con `static_folder='static'` y `static_url_path='/static'`.
   - Habilite `CORS(app)`.
   - Registre `habitaciones_bp`.
   - Sirva los archivos HTML desde la carpeta `html/` en las rutas `/`, `/index3.html`, `/tables/data.html` y `/crud.html` usando `send_from_directory`.
   - Registre un manejador global `@app.errorhandler(DatabaseUnavailableError)` que devuelva `jsonify({"ok": False, "error": str(e)}), 503`.

10. Explicarme en 5 puntos breves cómo gestionar los Pull Requests (PR) de las ramas de mis compañeros (`feat/i2-kpis`, `feat/i3-crud`, `feat/i4-sqlserver`, `feat/i5-dashboard`, `feat/i6-listado-crud-qa`) y qué debo responder al docente en la exposición.

PLAZO: los otros 5 integrantes no pueden probar nada hasta que mi esqueleto esté en `main`. Guíame para dejarlo subido (commit y push a `main`) lo antes posible, e indícame cuándo avisar al grupo para que hagan `git pull` y creen sus ramas.

Empieza ahora mismo dándome el PASO 1 (cargar y verificar el script oficial `sistema_residencia.sql` en MySQL).
```

---

### 👤 INTEGRANTE 2: Especialista Backend de KPIs y Gráfica Comparativa

> **Instrucción para el Integrante 2:** Copia todo el bloque de abajo y pégalo en tu IA.

```markdown
Actúa como mi Director Técnico de Software Senior. Estoy en el Grupo 1 (Módulo: Habitaciones) de un proyecto universitario en Flask + MySQL + AdminLTE 4.

REGLA DE ORO DE INTERACCIÓN:
Tú eres quien manda y dirige. Yo soy el operador humano y solo voy a ejecutar tus órdenes paso a paso.
- NO me des explicaciones largas ni todos los pasos juntos.
- Dime UN SOLO PASO a la vez, con el código exacto listo para copiar y pegar.
- Al final de cada paso pregúntame: "¿Ya lo hiciste? Pega aquí lo que salió o dime 'Listo' para avanzar al siguiente paso".

CONTEXTO DE ARQUITECTURA COMÚN:
- Trabajamos en la carpeta `grupo1_habitaciones/` sobre mi rama de Git `feat/i2-kpis`.
- Ya existen `db_mysql.py` con la función `query(sql, params=None, fetchone=False, commit=False)` (que devuelve diccionarios con `DictCursor`) y `habitaciones/__init__.py` donde está definido `habitaciones_bp = Blueprint('habitaciones', __name__, url_prefix='/api/habitaciones')`.
- REGLA ESTRICTA DEL DOCENTE (Sección 3.5): Todos los KPIs se calculan en SQL (MySQL), NUNCA en JavaScript ni en bucles de Python. Todo indicador de razón o porcentaje debe evitar la división entre cero usando `NULLIF(..., 0)` y `COALESCE(..., 0)` y devolver `0` si el divisor es cero.
- REGLA DE TIPOS JSON: en MySQL `SUM()` y `ROUND()` devuelven `DECIMAL` y Flask lo convierte en TEXTO (`"78.3"`), lo que rompe Postman y el JavaScript. Todo número de `/kpis` y `/grafico` debe salir como número JSON: `CAST(... AS UNSIGNED)` (o `SIGNED`) para enteros y `CAST(... AS DOUBLE)` para la tasa. Si tu versión de MySQL no acepta `AS DOUBLE`, convierte solo el tipo con `float()` en Python (sin recalcular nada).

MI ROL (INTEGRANTE 2 - BACKEND DE KPIS Y GRÁFICA COMPARATIVA):
Debo programar el archivo `habitaciones/kpis_grafico.py` con dos endpoints REST en JSON:

1. `GET /api/habitaciones/kpis` (Código HTTP `200`, o `503` si falla la BD):
   Calcula en SQL sobre las tablas `habitaciones` y `residentes` los 6 indicadores oficiales de la Sección 5.2 y devuelve tanto los valores numéricos como las fórmulas documentadas:
   - KPI 1 (`total_habitaciones`): `COUNT(*)` de `habitaciones` -> Valor oficial con datos del script: `15`
   - KPI 2 (`capacidad_instalada`): `CAST(COALESCE(SUM(capacidad), 0) AS UNSIGNED)` de `habitaciones` -> Valor oficial: `24`
   - KPI 3 (`camas_operativas`): `CAST(COALESCE(SUM(CASE WHEN estado <> 'MANTENIMIENTO' THEN capacidad ELSE 0 END), 0) AS UNSIGNED)` -> Valor oficial: `23`
   - KPI 4 (`tasa_ocupacion`): Residentes `ACTIVO` con habitación (`id_habitacion IS NOT NULL`) ÷ `camas_operativas` × 100, calculado en SQL con `CAST(ROUND(COALESCE(res.activos_con_hab * 100.0 / NULLIF(hab.camas_operativas, 0), 0), 1) AS DOUBLE)` -> Valor oficial: `78.3` (`18 ÷ 23 × 100 = 78.3%`)
   - KPI 5 (`camas_libres`): `camas_operativas - residentes_activos_con_habitacion` (también como entero, `CAST(... AS SIGNED)`) -> Valor oficial: `5` (`23 − 18 = 5`)
   - KPI 6 (`por_estado`): `COUNT(*)` agrupado por `estado` -> Valor oficial: `{"DISPONIBLE": 5, "OCUPADA": 9, "MANTENIMIENTO": 1}`
   - Formato JSON exacto que espera el Frontend (Integrante 5):
     ```json
     {
       "ok": true,
       "data": {
         "total_habitaciones": 15,
         "capacidad_instalada": 24,
         "camas_operativas": 23,
         "residentes_ocupando": 18,
         "tasa_ocupacion": 78.3,
         "camas_libres": 5,
         "por_estado": {
           "DISPONIBLE": 5,
           "OCUPADA": 9,
           "MANTENIMIENTO": 1
         },
         "formulas": {
           "total_habitaciones": "COUNT(*) de habitaciones",
           "capacidad_instalada": "SUM(capacidad)",
           "camas_operativas": "SUM(capacidad) de las habitaciones con estado distinto de MANTENIMIENTO",
           "tasa_ocupacion": "Residentes ACTIVOS con habitación ÷ camas operativas × 100",
           "camas_libres": "Camas operativas − residentes ACTIVOS con habitación",
           "por_estado": "COUNT(*) agrupado por estado"
         }
       }
     }
     ```

2. `GET /api/habitaciones/grafico` (Código HTTP `200`):
   - Gráfica comparativa de barras agrupadas: **Capacidad de camas frente a residentes asignados, por piso** (Sección 5.3).
   - **CUIDADO TÉCNICO CRÍTICO EN EL SQL:** NO hagas `SUM(h.capacidad)` después de un `LEFT JOIN residentes` directo, porque si una habitación doble tiene 2 residentes se duplica la fila de la habitación y el Piso 1 sumaría 14 camas en vez de 8.
   - Usa una subconsulta que primero agrupe los residentes activos por `id_habitacion` (o por piso) antes de unir con `habitaciones`:
     ```sql
     SELECT 
         h.piso,
         CAST(SUM(h.capacidad) AS UNSIGNED) AS capacidad_camas,
         CAST(COALESCE(SUM(r_cnt.total_res), 0) AS UNSIGNED) AS residentes_asignados
     FROM habitaciones h
     LEFT JOIN (
         SELECT id_habitacion, COUNT(*) AS total_res
         FROM residentes
         WHERE estado = 'ACTIVO' AND id_habitacion IS NOT NULL
         GROUP BY id_habitacion
     ) r_cnt ON r_cnt.id_habitacion = h.id_habitacion
     GROUP BY h.piso
     ORDER BY h.piso ASC
     ```
   - Valores de referencia oficiales que DEBE arrojar la consulta:
     - Piso 1: `8` camas / `6` residentes
     - Piso 2: `8` camas / `6` residentes
     - Piso 3: `8` camas / `6` residentes
   - Formato JSON exacto que espera el Frontend (Integrante 5):
     ```json
     {
       "ok": true,
       "total": 3,
       "categorias": ["Piso 1", "Piso 2", "Piso 3"],
       "series": [
         { "nombre": "Capacidad de camas", "datos": [8, 8, 8] },
         { "nombre": "Residentes asignados", "datos": [6, 6, 6] }
       ],
       "data": [
         { "piso": 1, "etiqueta": "Piso 1", "capacidad_camas": 8, "residentes_asignados": 6 },
         { "piso": 2, "etiqueta": "Piso 2", "capacidad_camas": 8, "residentes_asignados": 6 },
         { "piso": 3, "etiqueta": "Piso 3", "capacidad_camas": 8, "residentes_asignados": 6 }
       ]
     }
     ```

3. Guíame para crear mi rama `feat/i2-kpis`, escribir `habitaciones/kpis_grafico.py`, probar ambos endpoints con `curl` verificando los valores oficiales y que NINGÚN número salga entre comillas, comprobar que `python app.py` arranca sin errores de importación (un error en mi módulo tumbaría la app de todos) y hacer el Pull Request a `main`.

Empieza ahora mismo con el PASO 1.
```

---

### 👤 INTEGRANTE 3: Especialista Backend de CRUD, Reglas de Negocio y Listado Filtrado

> **Instrucción para el Integrante 3:** Copia todo el bloque de abajo y pégalo en tu IA.

```markdown
Actúa como mi Director Técnico de Software Senior. Estoy en el Grupo 1 (Módulo: Habitaciones) de un proyecto universitario en Flask + MySQL + AdminLTE 4.

REGLA DE ORO DE INTERACCIÓN:
Tú eres quien manda y dirige. Yo soy el operador humano y solo voy a ejecutar tus órdenes paso a paso.
- Dime UN SOLO PASO a la vez, con el código exacto listo para copiar y pegar.
- Al final de cada paso pregúntame: "¿Ya lo ejecutaste? Pega aquí el resultado o dime 'Listo' para darte el siguiente paso".

CONTEXTO DE ARQUITECTURA COMÚN:
- Trabajamos en la carpeta `grupo1_habitaciones/` sobre mi rama de Git `feat/i3-crud`.
- Ya existen `db_mysql.py` con la función `query(sql, params=None, fetchone=False, commit=False)` y `habitaciones/__init__.py` con `habitaciones_bp` (`url_prefix='/api/habitaciones'`).
- Todas las consultas SQL deben ser 100% parametrizadas con `%s` (nunca concatenar valores en el SQL).
- Formato estándar JSON (Sección 3.3):
  - Éxito: `{"ok": true, "total": N, "data": [...]}`
  - Error: `{"ok": false, "error": "Mensaje claro"}`
- Códigos HTTP estrictos: `200` (OK), `201` (Creado), `204` (Eliminado sin cuerpo), `400` (Datos inválidos), `404` (No existe), `409` (Conflicto: duplicado o regla de negocio), `503` (BD no disponible).
- RUTAS: el listado y el `POST` se declaran con `@habitaciones_bp.route('', methods=[...])` (cadena vacía, NO `'/'`), para que `/api/habitaciones` funcione sin redirección `308`.

MI ROL (INTEGRANTE 3 - BACKEND DE CRUD Y LISTADO CON FILTROS):
Debo construir el archivo `habitaciones/routes.py` con los 5 endpoints de mantenimiento y listado sobre `habitaciones (id_habitacion, numero, piso, capacidad, estado, observaciones)`:

1. `GET /api/habitaciones` (Listado con 4 filtros combinables con `AND` por query string - Sección 5.6):
   - Parámetros opcionales:
     - `estado`: si no está vacío, validar que sea `DISPONIBLE`, `OCUPADA` o `MANTENIMIENTO` (si envían otro valor, devolver HTTP `400`).
     - `piso`: si no está vacío, validar que sea un entero `> 0` (si no es entero válido o `<= 0`, devolver HTTP `400`).
     - `capacidad`: si no está vacío, validar que sea un entero `> 0` (si no es entero válido o `<= 0`, devolver HTTP `400`).
     - `q`: si no está vacío, filtra con `AND (h.numero LIKE %s OR COALESCE(h.observaciones, '') LIKE %s)`.
   - Un filtro vacío no se aplica.
   - **CRITERIO UNIFICADO CON DELETE:** Cada fila devuelta debe incluir `residentes_asignados` contando TODOS los residentes vinculados a esa habitación (`SELECT id_habitacion, COUNT(*) AS residentes_asignados FROM residentes WHERE id_habitacion IS NOT NULL GROUP BY id_habitacion`), exactamente con el mismo criterio que usa `DELETE` para bloquear el borrado. Así nunca aparecerá `0 residentes` en la tabla si la habitación tiene algún residente que impida borrarla.
   - Caso de prueba oficial (Sección 5.8): `GET /api/habitaciones?estado=DISPONIBLE&piso=1` debe devolver `"total": 2` con las habitaciones `"103"` y `"105"`.

2. `GET /api/habitaciones/<int:id_habitacion>`:
   - Devuelve HTTP `200` con `{"ok": true, "data": {...}}` (incluyendo `residentes_asignados`), o HTTP `404` con `{"ok": false, "error": "No existe la habitación solicitada"}`.

3. `POST /api/habitaciones` (Insertar registro - Sección 5.5):
   - Validaciones de entrada (devolver HTTP `400` con `{"ok": false, "error": "..."}` si falla alguna):
     - Cuerpo JSON válido.
     - `numero`: obligatorio, texto sin espacios extremos (`strip()`), no vacío, longitud máxima 10 caracteres.
     - `piso`: obligatorio, entero mayor que 0 (`piso > 0`). Debe ser un entero real de JSON: rechazar con `400` los booleanos (`true/false`), textos y decimales (en Python `True` cuenta como entero, así que valida con `isinstance(valor, int) and not isinstance(valor, bool)`).
     - `capacidad`: obligatorio, entero mayor que 0 (`capacidad > 0`), con la misma regla de tipo que `piso`.
     - `estado`: obligatorio (por defecto `'DISPONIBLE'` si no se envía), debe ser `'DISPONIBLE'`, `'OCUPADA'` o `'MANTENIMIENTO'`.
     - `observaciones`: opcional (`None` o string de máximo 255 caracteres).
   - Validación de duplicado (HTTP `409`): Si ya existe una habitación con ese `numero`, responder `409` con `{"ok": false, "error": f"Ya existe la habitación {numero}"}`. (Caso de prueba oficial: enviar `numero: "101"` devuelve `409`).
   - Si pasa las validaciones, inserta en MySQL y devuelve HTTP `201` con `{"ok": true, "data": {...}}`.

4. `PUT /api/habitaciones/<int:id_habitacion>` (Actualizar registro - Sección 5.5):
   - Primero verifica si existe `id_habitacion`; si no existe, responde HTTP `404`.
   - Aplica las mismas validaciones de formato que `POST` (HTTP `400`).
   - Verifica duplicado de `numero` en otra habitación (`WHERE numero = %s AND id_habitacion <> %s`); si existe, responde HTTP `409`.
   - Verifica coherencia con residentes asignados SOLO cuando se REDUCE la capacidad: lee la `capacidad` actual de la habitación y cuenta sus residentes (`SELECT COUNT(*) AS total FROM residentes WHERE id_habitacion = %s`). Si `capacidad_nueva < capacidad_actual` **y** `capacidad_nueva < total`, responde HTTP `409` indicando que la capacidad no puede ser menor a los residentes actualmente asignados. Si la capacidad no cambia o aumenta, NO se aplica esta regla. Motivo: en los datos oficiales las habitaciones `303` y `305` ya tienen capacidad `1` con `2` residentes, y editar sus observaciones debe responder `200`.
   - Actualiza y responde HTTP `200` con `{"ok": true, "data": {...}}`.

5. `DELETE /api/habitaciones/<int:id_habitacion>` (Eliminar registro - Sección 5.5):
   - Primero verifica si existe `id_habitacion`; si no existe, responde HTTP `404`.
   - **REGLA DE NEGOCIO OBLIGATORIA (Sección 5.5):** Consulta `SELECT COUNT(*) AS total FROM residentes WHERE id_habitacion = %s`. Si `total > 0`, NO elimina la habitación y responde HTTP `409` con `{"ok": false, "error": "No se puede eliminar la habitación porque tiene residentes asignados"}` (evitando que `ON DELETE SET NULL` los deje sin habitación sin avisar).
   - Si `total == 0`, elimina el registro y responde código HTTP `204` (sin cuerpo).

Guíame paso a paso desde el PASO 1 (crear mi rama `feat/i3-crud` y escribir `habitaciones/routes.py`).
```

---

### 👤 INTEGRANTE 4: Especialista en SQL Server y Transferencia de Datos (`MERGE`)

> **Instrucción para el Integrante 4:** Copia todo el bloque de abajo y pégalo en tu IA.

```markdown
Actúa como mi Director Técnico de Software Senior y DBA. Estoy en el Grupo 1 (Módulo: Habitaciones) de un proyecto universitario en Flask + MySQL + SQL Server (`pyodbc`).

REGLA DE ORO DE INTERACCIÓN:
Tú eres quien manda y dirige. Yo soy el operador humano y solo voy a ejecutar tus órdenes paso a paso.
- Dime UN SOLO PASO a la vez (cómo configurar SQL Server, qué script correr en SSMS o qué archivo Python crear).
- Al final de cada paso pregúntame: "¿Ya lo ejecutaste? Cuéntame qué salió o dime 'Listo' para avanzar al siguiente paso".

MI ROL (INTEGRANTE 4 - SQL SERVER Y TRANSFERENCIA ETL CON MERGE):
Trabajo en la rama `feat/i4-sqlserver`. Soy responsable de la base de datos destino en SQL Server y de la API de transferencia idempotente desde MySQL hacia SQL Server. Mis tareas exactas son:

1. Configurar SQL Server (Express o Developer) con TCP/IP habilitado en el puerto `1433`, base de datos `residencia_sqlserver` y usuario propio `residencia_sql` con permisos sobre `residencia_sqlserver` (sin usar `sa`, cumpliendo la Sección 3.6).

2. Crear el archivo `sql/sqlserver_habitaciones.sql` con el DDL exacto de la Sección 5.7 del documento del docente (incluyendo también en comentarios el comando `TRUNCATE TABLE dbo.habitaciones;` para reiniciar antes de las pruebas):
   ```sql
   USE residencia_sqlserver;
   GO

   IF OBJECT_ID('dbo.habitaciones', 'U') IS NULL
   BEGIN
       CREATE TABLE dbo.habitaciones (
           id_habitacion       INT           NOT NULL PRIMARY KEY,  -- sin IDENTITY
           numero              NVARCHAR(10)  NOT NULL UNIQUE,
           piso                INT           NOT NULL CHECK (piso > 0),
           capacidad           INT           NOT NULL CHECK (capacidad > 0),
           estado              NVARCHAR(20)  NOT NULL DEFAULT 'DISPONIBLE'
               CHECK (estado IN ('DISPONIBLE','OCUPADA','MANTENIMIENTO')),
           observaciones       NVARCHAR(255) NULL,
           fecha_transferencia DATETIME2     NOT NULL DEFAULT SYSDATETIME()
       );
   END
   GO

   -- Comando para dejar la tabla vacía antes de correr Postman o la demostración en vivo:
   -- TRUNCATE TABLE dbo.habitaciones;
   ```

3. Crear `db_sqlserver.py` en la raíz `grupo1_habitaciones/`:
   - Construir la cadena de conexión `pyodbc` con `from config import Config` (el Integrante 1 define en `Config` los atributos `SQLSERVER_DRIVER`, `SQLSERVER_SERVER`, `SQLSERVER_DB`, `SQLSERVER_USER` y `SQLSERVER_PASSWORD`; si falta alguno, se lo pido a él y NO edito `config.py` yo mismo):
     ```python
     conn_str = (
         f"DRIVER={Config.SQLSERVER_DRIVER};"
         f"SERVER={Config.SQLSERVER_SERVER};"
         f"DATABASE={Config.SQLSERVER_DB};"
         f"UID={Config.SQLSERVER_USER};"
         f"PWD={Config.SQLSERVER_PASSWORD};"
         "TrustServerCertificate=yes;"
     )
     ```
   - Función `get_sqlserver_connection()` con `autocommit=False` para controlar la transacción manualmente (`commit` y `rollback`). Si la conexión falla, lanzar error para responder HTTP `503`.

4. Crear `habitaciones/transferencia.py` registrado en `habitaciones_bp` con dos endpoints:
   - `POST /api/habitaciones/transferir` (Sección 3.6 y 5.4):
     1. Inicia cronómetro (`time.perf_counter()`).
     2. Lee todos los registros de `habitaciones` desde MySQL (`SELECT id_habitacion, numero, piso, capacidad, estado, observaciones FROM habitaciones ORDER BY id_habitacion`). Si MySQL no está disponible, devuelve HTTP `503`.
     3. Abre conexión y transacción a SQL Server con `pyodbc`. Si SQL Server no está disponible, devuelve HTTP `503`.
     4. Ejecuta por cada fila la sentencia `MERGE` por la llave primaria `id_habitacion` con `OUTPUT $action;` al final (terminada obligatoriamente en punto y coma `;`) para saber con exactitud si esa fila fue `'INSERT'` o `'UPDATE'`:
        ```sql
        MERGE dbo.habitaciones AS d
        USING (VALUES (?,?,?,?,?,?)) AS s
              (id_habitacion, numero, piso, capacidad, estado, observaciones)
          ON d.id_habitacion = s.id_habitacion
        WHEN MATCHED THEN UPDATE SET
             d.numero = s.numero, d.piso = s.piso, d.capacidad = s.capacidad,
             d.estado = s.estado, d.observaciones = s.observaciones,
             d.fecha_transferencia = SYSDATETIME()
        WHEN NOT MATCHED THEN INSERT
             (id_habitacion, numero, piso, capacidad, estado, observaciones)
             VALUES (s.id_habitacion, s.numero, s.piso, s.capacidad,
                     s.estado, s.observaciones)
        OUTPUT $action;
        ```
     5. Suma cuántos fueron `insertados` (`action == 'INSERT'`) y cuántos `actualizados` (`action == 'UPDATE'`).
     6. Ejecuta `conn.commit()`. Si ocurre cualquier error durante el lote, ejecuta `conn.rollback()` y responde HTTP `500` con `{"ok": false, "error": "Error durante la transferencia a SQL Server"}`.
     7. Devuelve HTTP `200` con el JSON oficial:
        ```json
        {
          "ok": true,
          "leidos": 15,
          "insertados": 15,
          "actualizados": 0,
          "duracion_ms": 212,
          "transferido_el": "2026-11-04T18:30:00"
        }
        ```

   - `GET /api/habitaciones/transferencia/resumen`:
     - Obtiene `COUNT(*)` de `habitaciones` en MySQL y `COUNT(*)`, `MAX(fecha_transferencia)` de `dbo.habitaciones` en SQL Server.
     - Devuelve HTTP `200` (o `503` si alguna BD no responde):
       ```json
       {
         "ok": true,
         "mysql_total": 15,
         "sqlserver_total": 15,
         "ultima_transferencia": "2026-11-04T18:30:00"
       }
       ```

5. Guíame para probar la idempotencia: primero vaciar con `TRUNCATE TABLE dbo.habitaciones;`, llamar a `POST /api/habitaciones/transferir` (verificar `insertados: 15, actualizados: 0`) y volver a llamarlo inmediatamente (verificar `insertados: 0, actualizados: 15`).

Empieza ahora mismo con el PASO 1.
```

---

### 👤 INTEGRANTE 5: Especialista Frontend del Dashboard (`index3.html`) y Menú AdminLTE 4

> **Instrucción para el Integrante 5:** Copia todo el bloque de abajo y pégalo en tu IA. *(Puedes empezar desde el Día 1 sin esperar al backend, porque los contratos JSON ya están fijados).*

```markdown
Actúa como mi Director Técnico Frontend Senior. Estoy en el Grupo 1 (Módulo: Habitaciones) de un proyecto universitario con Flask y la plantilla AdminLTE 4 (Bootstrap 5 + JavaScript `fetch`).

REGLA DE ORO DE INTERACCIÓN:
Tú eres quien manda y dirige. Yo soy el operador humano y solo voy a ejecutar tus órdenes paso a paso.
- Dime UN SOLO PASO a la vez, dándome el código HTML y JavaScript completo listo para copiar y pegar.
- Al final de cada paso pregúntame: "¿Ya guardaste el archivo y lo probaste en el navegador? Dime 'Listo' para darte el siguiente paso".

MI ROL (INTEGRANTE 5 - FRONTEND DASHBOARD, GRÁFICA Y BOTÓN DE TRANSFERENCIA):
Trabajo en la rama `feat/i5-dashboard`. Puedo avanzar desde el primer día porque la estructura JSON de las APIs ya está definida. Mis entregables son `html/index3.html` y `static/js/dashboard.js`. Antes de empezar hago `git pull` de `main` para tener el esqueleto del Integrante 1 (incluye `static/dist/`). Hasta que existan los endpoints del Integrante 2 y del Integrante 4, pruebo con datos de ejemplo fijos en JavaScript.

REGLAS OBLIGATORIAS DE DISEÑO Y RUTAS (Secciones 3.4, 5.1, 5.2, 5.3 y 5.7):
1. **Rutas absolutas `/static/...` en el HTML:**
   - Todos los enlaces a CSS, JS e imágenes de la plantilla AdminLTE 4 en el HTML deben usar rutas absolutas desde la raíz del servidor Flask (`/static/dist/css/adminlte.min.css`, `/static/dist/js/adminlte.min.js`, `/static/js/dashboard.js`, además de Bootstrap 5, Bootstrap Icons, ApexCharts —la librería de gráficos que ya usa AdminLTE 4; no mezclar con Chart.js— y SweetAlert2), NUNCA rutas relativas como `./dist/` o `../dist/`, para que el mismo encabezado y menú funcione idéntico cuando el Integrante 6 lo copie en `/tables/data.html`.
2. **Menú Lateral (Sidebar AdminLTE 4) idéntico para todas las páginas del módulo (Sección 3.4):**
   - Marca superior: `Residencia · Grupo 1 (Habitaciones)`
   - Opción 1: `Dashboard` -> `/index3.html`
   - Opción 2: `Listado de habitaciones` -> `/tables/data.html`
   - Opción 3: `Mantenimiento de habitaciones` -> `/crud.html`
   - Opción 4: `Transferencia a SQL Server` -> `/index3.html#card-transferencia`
   - Entrego pronto un primer commit/PR con el bloque HTML del sidebar terminado, para que el Integrante 6 lo copie idéntico en `tables/data.html` y `crud.html`.
3. **Tarjetas de los 6 KPIs del Grupo 1** (consumiendo `GET /api/habitaciones/kpis`):
   Cada tarjeta debe mostrar el valor numérico grande y debajo la **fórmula de cálculo documentada** (requisito obligatorio de la Sección 2 y 5.2):
   - KPI 1: **Total de habitaciones** (`data.total_habitaciones` -> ref: `15`) | Fórmula: `COUNT(*) de habitaciones`
   - KPI 2: **Capacidad instalada (camas)** (`data.capacidad_instalada` -> ref: `24`) | Fórmula: `SUM(capacidad)`
   - KPI 3: **Camas operativas** (`data.camas_operativas` -> ref: `23`) | Fórmula: `SUM(capacidad) con estado ≠ MANTENIMIENTO`
   - KPI 4: **Tasa de ocupación (%)** (`data.tasa_ocupacion` -> ref: `78.3 %`) | Fórmula: `Residentes ACTIVOS con hab. ÷ camas operativas × 100`
   - KPI 5: **Camas libres** (`data.camas_libres` -> ref: `5`) | Fórmula: `Camas operativas − residentes ACTIVOS con hab.`
   - KPI 6: **Habitaciones por estado** (`data.por_estado` -> badges para `DISPONIBLE: 5`, `OCUPADA: 9`, `MANTENIMIENTO: 1`) | Fórmula: `COUNT(*) agrupado por estado`
4. **Gráfica Comparativa por Piso** (consumiendo `GET /api/habitaciones/grafico`):
   - Gráfico de **Barras Agrupadas** comparando dos series en `categorias` (`["Piso 1", "Piso 2", "Piso 3"]`):
     - Serie 1: `Capacidad de camas` (`[8, 8, 8]`)
     - Serie 2: `Residentes asignados` (`[6, 6, 6]`)
   - Complemento opcional de la Sección 5.3: Gráfico circular (Donut) al costado con la distribución de habitaciones por estado (`DISPONIBLE`, `OCUPADA`, `MANTENIMIENTO`).
5. **Tarjeta y Botón de Transferencia a SQL Server (`#card-transferencia`):**
   - Al abrir el dashboard, llama a `GET /api/habitaciones/transferencia/resumen` y muestra los contadores: **Registros en MySQL**, **Registros en SQL Server** y **Última transferencia**.
   - El botón **Transferir a SQL Server**:
     1. Pide confirmación al usuario antes de ejecutar.
     2. Muestra un spinner de carga y deshabilita el botón mientras espera respuesta.
     3. Llama a `POST /api/habitaciones/transferir`.
     4. Muestra el resultado detallado (`leidos`, `insertados`, `actualizados`, `duracion_ms`, `transferido_el`) y vuelve a llamar a `/api/habitaciones/transferencia/resumen` para actualizar la tarjeta en pantalla.

Empieza ahora mismo con el PASO 1 (crear la rama `feat/i5-dashboard` y construir `html/index3.html`).
```

---

### 👤 INTEGRANTE 6: Especialista Frontend de Listado con Filtros, Formulario CRUD y Pruebas Postman

> **Instrucción para el Integrante 6:** Copia todo el bloque de abajo y pégalo en tu IA. *(Puedes empezar desde el Día 1 en paralelo con el resto del grupo).*

```markdown
Actúa como mi Director Técnico Frontend y QA Senior. Estoy en el Grupo 1 (Módulo: Habitaciones) de un proyecto universitario con Flask, MySQL, SQL Server y AdminLTE 4.

REGLA DE ORO DE INTERACCIÓN:
Tú eres quien manda y dirige. Yo soy el operador humano y solo voy a ejecutar tus órdenes paso a paso.
- Dime UN SOLO PASO a la vez, con el código completo listo para copiar y pegar.
- Al final de cada paso pregúntame: "¿Ya lo probaste? Dime 'Listo' o pégame cualquier duda para darte el siguiente paso".

MI ROL (INTEGRANTE 6 - LISTADO FILTRADO, FORMULARIO CRUD Y COLECCIÓN POSTMAN):
Trabajo en la rama `feat/i6-listado-crud-qa`. Puedo avanzar desde el Día 1 porque los endpoints y formatos JSON ya están definidos. Antes de empezar hago `git pull` de `main` (esqueleto del Integrante 1 con `static/dist/`). Para ver datos reales en listado y CRUD necesito los endpoints del Integrante 3; el menú lo copio idéntico del Integrante 5 (o sigo la lista de opciones de arriba si aún no lo ha subido); y para la colección Postman completa necesito los endpoints de los Integrantes 2, 3 y 4. Tengo a mi cargo cuatro entregables:

1. **Página de Listado con Filtros (`html/tables/data.html` y `static/js/listado.js` - Sección 5.6):**
   - **IMPORTANTE SOBRE RUTAS:** Como `html/tables/data.html` se sirve en `/tables/data.html`, TODOS los CSS, JS e imágenes deben usar rutas absolutas con `/static/...` (por ejemplo `/static/dist/css/adminlte.min.css` y `/static/js/listado.js`), nunca rutas relativas `../../`.
   - Conserva exactamente el mismo menú lateral AdminLTE 4 del módulo (`Dashboard` -> `/index3.html`, `Listado de habitaciones` -> `/tables/data.html`, `Mantenimiento de habitaciones` -> `/crud.html`, `Transferencia a SQL Server` -> `/index3.html#card-transferencia`).
   - Tarjeta de filtros sobre la tabla con los **4 filtros del Grupo 1** (Sección 5.1 y 5.6: el cuarto filtro `capacidad` sustituye al filtro de fecha porque `habitaciones` no tiene columna de fecha):
     - `Estado` (select `estado`: Todos, `DISPONIBLE`, `OCUPADA`, `MANTENIMIENTO`)
     - `Piso` (select `piso`: Todos, `1`, `2`, `3`)
     - `Capacidad` (select `capacidad`: Todas, `1` cama, `2` camas)
     - `Búsqueda` (input texto `q`: busca en `numero` y `observaciones`)
   - Botones **Filtrar** (envía query string combinando con `&` solo los filtros no vacíos a `GET /api/habitaciones`) y **Limpiar** (limpia los 4 controles y recarga toda la tabla).
   - Columnas de la tabla: `ID`, `Número`, `Piso`, `Capacidad`, `Residentes asignados` (`residentes_asignados`), `Estado` (badge verde/azul/amarillo), `Observaciones` y `Acciones` (botón **Editar** que redirige a `/crud.html?id=<id_habitacion>` y botón **Eliminar** con confirmación que llama a `DELETE /api/habitaciones/<id_habitacion>`).

2. **Página de Mantenimiento CRUD (`html/crud.html` y `static/js/crud.js` - Sección 5.5):**
   - Mismo menú lateral AdminLTE 4 y rutas absolutas `/static/...`.
   - Si la URL trae `?id=<id>`, carga los datos desde `GET /api/habitaciones/<id>` en modo **Edición (`PUT /api/habitaciones/<id>`)**; si no trae `id`, funciona en modo **Nuevo Registro (`POST /api/habitaciones`)**. Incluye también una tabla resumen en la parte inferior para seleccionar, editar o eliminar desde la misma vista.
   - Campos y validaciones del formulario (Sección 5.5):
     - `numero`: obligatorio, no vacío, máx. 10 caracteres (si la API responde `409`, mostrar el mensaje de error `"Ya existe la habitación ..."`).
     - `piso`: obligatorio, entero mayor que 0.
     - `capacidad`: obligatorio, entero mayor que 0.
     - `estado`: lista desplegable (`DISPONIBLE` por defecto, `OCUPADA`, `MANTENIMIENTO`).
     - `observaciones`: opcional, máx. 255 caracteres.
   - Al eliminar (`DELETE /api/habitaciones/<id>`), pedir confirmación previa; si responde `204`, mostrar mensaje de éxito y recargar; si responde `409` (porque tiene residentes asignados), mostrar una alerta clara con el motivo devuelto por la API.

3. **Colección Postman (`postman/coleccion.json` - Sección 5.8 y 11):**
   - Generar el archivo `postman/coleccion.json` (formato Postman Collection v2.1) con la variable `{{base_url}} = http://127.0.0.1:5000` y 4 carpetas (`KPIs`, `Listado con filtros`, `CRUD`, `Transferencia`). **Orden obligatorio de las carpetas: `KPIs` → `Listado con filtros` → `CRUD` → `Transferencia`**, para que los tests de KPIs y filtros corran sobre los datos oficiales sin alterar.
   - Cada petición debe llevar en la pestaña **Tests** validaciones con `pm.test(...)` comprobando el código HTTP y el campo `ok`, incluyendo los 4 casos de prueba obligatorios de la Sección 5.8:
     1. `GET {{base_url}}/api/habitaciones/kpis` -> `200`, `tasa_ocupacion == 78.3` y `camas_libres == 5`.
     2. `GET {{base_url}}/api/habitaciones?estado=DISPONIBLE&piso=1` -> `200`, `total == 2` (habitaciones `103` y `105`).
     3. `POST {{base_url}}/api/habitaciones` con `{"numero": "101", "piso": 1, "capacidad": 2, "estado": "DISPONIBLE"}` -> `409` (`ok == false`).
     4. `POST {{base_url}}/api/habitaciones/transferir` -> `200`, `leidos == 15` (nota: recordar al equipo ejecutar antes `TRUNCATE TABLE dbo.habitaciones;` en SQL Server para que la 1.ª ejecución valide `insertados == 15` y la 2.ª ejecución valide `insertados == 0` y `actualizados == 15`).
   - **La carpeta `CRUD` se limpia a sí misma** (nunca dejar datos de prueba en MySQL): `POST` de una habitación temporal `{"numero": "999", "piso": 9, "capacidad": 1}` -> `201` (guardar su `id_habitacion` con `pm.collectionVariables.set`), `GET` por id -> `200`, `PUT` -> `200`, `POST` duplicado de `"101"` -> `409`, `DELETE` de la temporal -> `204` y `GET` posterior -> `404`.
   - Además: `DELETE /api/habitaciones/1` (habitación `101`, con residentes) -> `409`, y `PUT /api/habitaciones/13` (habitación `303`) enviando sus mismos valores actuales (`numero "303"`, `piso 3`, `capacidad 1`, `estado "OCUPADA"`, `observaciones "Habitación individual"`) -> `200`, para comprobar que editar una habitación ya sobre su capacidad no se bloquea.

4. **Informe PDF consolidado (`docs/informe_grupo1.pdf` - Sección 12):**
   - Informe breve con portada y capturas de: dashboard (KPIs y gráfica), listado con filtros, CRUD (incluyendo los errores `409`), transferencia (resultado en pantalla y la tabla `dbo.habitaciones` verificada en SSMS, primera y segunda ejecución) y la ejecución de la colección Postman.
   - Cada integrante deja las capturas de su parte en `docs/capturas/` (por ejemplo `i2_kpis_curl.png`) y yo las consolido en el PDF.

Empieza ahora mismo con el PASO 1 (crear mi rama `feat/i6-listado-crud-qa` y construir `html/tables/data.html`).
```

---

## 4. Flujo de Trabajo e Integración Continua por Etapas

En lugar de esperar al último día para unir el proyecto, el equipo integra sus avances **al terminar cada etapa**, pudiendo empezar todos en paralelo desde el Día 1:

1. **Etapa 1 — Base y Entorno (Día 1):**
   - El **Integrante 1** ejecuta el script oficial `database/sistema_residencia.sql` en MySQL y sube a `main` el esqueleto `grupo1_habitaciones/` con `app.py`, `config.py`, `db_mysql.py`, `.env.example` (sin contraseñas) y `habitaciones/__init__.py` (con los imports al final y los 3 archivos vacíos de rutas). También sube `database/sistema_residencia.sql` y `static/dist/` (AdminLTE 4). **Este PR se hace en las primeras horas: el resto del grupo hace `git pull` de `main` y crea su rama a partir de ahí.**
   - **En paralelo desde el Día 1**, los **Integrantes 5 y 6** empiezan a maquetar `index3.html`, `tables/data.html` y `crud.html` usando rutas absolutas `/static/...`.
2. **Etapa 2 — Integración de KPIs, Gráfica y Dashboard:**
   - El **Integrante 2** hace Pull Request de `feat/i2-kpis` (`habitaciones/kpis_grafico.py`) y el **Integrante 5** hace Pull Request de `feat/i5-dashboard` (`html/index3.html` y `static/js/dashboard.js`).
   - Verifican juntos en `http://127.0.0.1:5000/index3.html` que las tarjetas muestren `15` habitaciones, `24` camas instaladas, `23` operativas, `78.3 %` de ocupación, `5` camas libres y que la gráfica muestre `8 camas / 6 residentes` en los Pisos 1, 2 y 3.
3. **Etapa 3 — Integración de CRUD y Listado con Filtros:**
   - El **Integrante 3** hace Pull Request de `feat/i3-crud` (`habitaciones/routes.py`) y el **Integrante 6** hace Pull Request de `feat/i6-listado-crud-qa` (`html/tables/data.html`, `html/crud.html`, `static/js/listado.js`, `static/js/crud.js`).
   - Verifican juntos el filtro `Estado = DISPONIBLE` + `Piso = 1` (salen `103` y `105`), la creación/edición en `crud.html`, el error `409` al intentar duplicar la `101` y el bloqueo `409` al intentar eliminar una habitación ocupada.
4. **Etapa 4 — Integración de SQL Server y Transferencia (`MERGE`):**
   - El **Integrante 4** ejecuta `sql/sqlserver_habitaciones.sql` en SSMS y hace Pull Request de `feat/i4-sqlserver` (`db_sqlserver.py` y `habitaciones/transferencia.py`).
   - Prueban el botón **Transferir a SQL Server** desde `index3.html`.
5. **Etapa 5 — Protocolo antes de correr Postman y antes de la Demostración en Vivo:**
   - **Paso obligatorio de preparación:** Antes de ejecutar la colección `postman/coleccion.json` o iniciar la exposición frente al docente:
     1. Verificar que MySQL tenga las 15 habitaciones originales del script oficial `database/sistema_residencia.sql`.
     2. Ejecutar en SQL Server Management Studio (SSMS):
        ```sql
        USE residencia_sqlserver;
        TRUNCATE TABLE dbo.habitaciones;
        ```
     3. Así, cuando presionen el botón **Transferir** (o corran el test en Postman) por **primera vez**, el resultado será exactamente `leidos: 15, insertados: 15, actualizados: 0`, y al presionarlo por **segunda vez** frente al profesor mostrará `leidos: 15, insertados: 0, actualizados: 15`, demostrando que el `MERGE` no duplica registros.
6. **Etapa 6 — Pruebas finales, informe y demostración:**
   - Con todo integrado en `main` y hecho el protocolo de la Etapa 5, ejecutar la colección Postman completa y guardar las capturas del resultado.
   - Cada integrante deja en `docs/capturas/` las capturas de su parte; el **Integrante 6** consolida `docs/informe_grupo1.pdf`.
   - Ensayo general de la demostración en vivo, en este orden: dashboard → filtros → CRUD (incluyendo los `409`) → botón **Transferir** dos veces → verificación en SSMS. Cada integrante demuestra su parte.

---

## 5. Mapa de Dependencias entre Integrantes

Casi todo depende de un solo punto: el **esqueleto del Integrante 1 en `main`** (`config.py`, `db_mysql.py`, `habitaciones_bp`, `app.py`, `static/dist/` y los archivos semilla). Después, cada integrante trabaja en archivos distintos, así que los Pull Requests no se pisan entre sí.

| Integrante | ¿Necesita el esqueleto del I1? | ¿Necesita a otros integrantes? |
| :--- | :--- | :--- |
| **Integrante 1** | — | No. Solo MySQL 8 y el script oficial del docente. |
| **Integrante 2** | Sí (`db_mysql.py`, `habitaciones_bp`) | No. Se prueba solo con `curl`. |
| **Integrante 3** | Sí (`db_mysql.py`, `habitaciones_bp`) | No. Se prueba solo con `curl` o Postman. |
| **Integrante 4** | Sí (`Config` con `SQLSERVER_*`, `db_mysql.py`, `habitaciones_bp`) | No depende de I2 ni I3 (lee la tabla directo de MySQL). Necesita su propio SQL Server instalado. |
| **Integrante 5** | Sí (`static/dist/`, `app.py`) | Maqueta con datos de ejemplo. Para ver datos reales necesita a I2 (`/kpis`, `/grafico`) e I4 (`/transferir`, `/transferencia/resumen`). |
| **Integrante 6** | Sí (`static/dist/`, `app.py`) | Listado y CRUD con datos reales: I3. Menú: copia el de I5. Colección Postman completa: I2, I3 e I4. Informe: capturas de todos. |

**Orden de integración sugerido:** I1 → I2, I3 e I4 (en cualquier orden) → I5 → I6 (Postman e informe al final).

**Precaución:** `habitaciones/__init__.py` importa los tres módulos, así que un error de importación en cualquiera de ellos tumba la app de todos. Antes de cada PR, correr `python app.py` y probar el endpoint propio.
