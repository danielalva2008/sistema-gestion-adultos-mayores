# Grupo 3 · Personal con MySQL 8 y SQL Server

Implementación del Grupo 3 del plan de Unidad II. Interfaz AdminLTE 4 y Bootstrap 5, API Flask, origen MySQL 8 con PyMySQL y destino SQL Server con pyodbc. No necesita XAMPP, Apache, PHP ni phpMyAdmin.

## Configuración de esta computadora

- MySQL Community Server 8.4.13: 127.0.0.1:3307, base `sistema_residencia`, contenedor existente `residencia-grupo3-mysql`.
- SQL Server existente: 127.0.0.1:1433, base `residencia_sqlserver`, contenedor `sql2025`.
- Flask: http://127.0.0.1:5000/.

Se conservaron los 16 trabajadores y todos los campos de las tablas de apoyo. La base anterior permanece intacta; la aplicación ya no se conecta a ella. Cada motor usa sus propias credenciales de aplicación en `.env`.

Para encender los servidores ya preparados en este equipo, si estuvieran detenidos:

```bash
docker start residencia-grupo3-mysql sql2025
bash iniciar.sh
```

Esto reutiliza los contenedores y sus datos. No crear contenedores duplicados ni borrar volúmenes. Docker aloja los motores en este Linux: no es un framework de la aplicación y no es obligatorio para compartir el proyecto.

## Usarlo en Windows o en otro Linux

1. Instalar Python 3.10 o posterior, **MySQL Server 8**, SQL Server Express/Developer o tener acceso a un SQL Server disponible, y Microsoft ODBC Driver 17/18. MySQL puede instalarse directamente; no requiere Docker ni XAMPP.
2. Tener la base `sistema_residencia`. Si ya existe, conservarla. Solo para una instalación vacía, importar manualmente `sql/mysql_sistema_residencia.sql`; contiene tablas y datos de clase, no tus nuevos registros. No ejecutarlo sobre una base con tablas existentes.
3. Preparar el destino con `sql/sqlserver_personal.sql` si aún falta. El script no borra tablas ni datos existentes.
4. Configurar usuarios propios: lectura en las tablas de apoyo y SELECT/INSERT/UPDATE/DELETE en Personal para el origen; SELECT/INSERT/UPDATE en dbo.personal para el destino. No usar root ni sa como cuentas de la aplicación. El ejemplo mysql_permisos.sql requiere que el usuario ya exista y que su host sea el correcto.
5. Copiar `.env.example` como `.env` y completar las credenciales de ese equipo. MySQL instalado directamente normalmente usa 3306. En esta computadora se usa 3307 para mantenerlo separado de otros servicios. SQL Server usa 1433 salvo configuración distinta.
6. Windows: doble clic en `iniciar.bat`. Linux: `bash iniciar.sh`.

El lanzador crea un entorno de Python propio y prepara dependencias. No inicia servicios de bases, importa SQL, altera credenciales ni ejecuta transferencias. No copiar `.venv` entre sistemas. La primera instalación de dependencias necesita internet; CSS, JavaScript, fuentes e iconos están incluidos localmente.

El acceso es por Flask. Abrir el HTML con doble clic no procesa las plantillas Jinja. No hay que copiar el proyecto a htdocs.

## Conexiones independientes y diagnóstico

MySQL utiliza las variables `MYSQL_*`; SQL Server, `SQLSERVER_*`. Cada copia debe tener su `.env` privado. Compartir código no comparte automáticamente datos: quienes configuren el mismo servidor remoto sí compartirán esa base.

`SQLSERVER_DRIVER=auto` selecciona ODBC Driver 18 o 17. Para una instancia de Windows puede usarse `SQLSERVER_SERVER=localhost\SQLEXPRESS` en vez de host y puerto. El driver nativo es adicional al paquete Python pyodbc.

```text
Windows: iniciar.bat --diagnostico
Linux:   bash iniciar.sh --diagnostico
```

El diagnóstico es de lectura y comprueba que el origen sea MySQL 8. Si falla SQL Server, los listados y el CRUD del origen siguen independientes. El puerto de Flask puede cambiarse con `FLASK_PORT`; el lanzador no cierra servicios ajenos.

En VS Code, seleccionar el intérprete `.venv` propio del equipo. Se conservan las tareas de inicio, diagnóstico y pruebas y la configuración de depuración. Los comentarios del código explican cada sección.

## Estructura requerida

```text
grupo3_personal/
├── app.py
├── config.py
├── db_mysql.py
├── db_sqlserver.py
├── modulo/
│   ├── routes.py
│   └── transferencia.py
├── sql/sqlserver_personal.sql
├── static/
├── html/
│   ├── index3.html
│   ├── tables/data.html
│   └── crud.html
├── postman/coleccion.json
├── .env.example
└── requirements.txt
```

Los lanzadores, validacion.py, base.html, pruebas y documentación son complementarios. Solo Personal tiene mantenimiento. Actividades, incidentes y residentes se leen para los indicadores.

## Funciones del Grupo 3

Seis KPIs SQL: personal activo, distribución por cargo, antigüedad promedio, residentes por cuidador, actividades por responsable e incidentes por reportante. Las razones devuelven cero con divisor cero. La gráfica compara actividades e incidentes por cargo; se conserva el complemento circular. Las fechas seleccionan personal según fecha_ingreso; la carga incluye sus registros asociados.

El listado combina cargo, estado, especialidad, fechas inclusivas y búsqueda. Las consultas usan parámetros. El CRUD valida campos, longitudes, correo, teléfono, estado, fecha no futura y código único. Un trabajador con actividades o incidentes no se elimina: responde 409 y recomienda INACTIVO.

Nueve rutas requeridas bajo `/api/personal`: GET `/kpis`, GET `/grafico`, GET listado, GET por ID, POST crear, PUT actualizar, DELETE eliminar, POST `/transferir` y GET `/transferencia/resumen`. `/catalogos` es auxiliar. Éxito con `ok`, `total` y `data`; error con `ok=false` y `error`. DELETE devuelve 204 sin cuerpo. La transferencia utiliza los conteos, duración y fecha específicos del Word.

MERGE copia toda la tabla Personal por ID, inserta o actualiza sin duplicar, conserva la PK sin IDENTITY y añade fecha_transferencia. Una transacción en el destino confirma con commit o revierte con rollback. No borra filas sobrantes del destino ni modifica el origen. El botón Transferir solicita confirmación.

## Pruebas y entrega

Las pruebas crean temporales y ejecutan la transferencia. Usarlas en una base de clase con respaldo. El diagnóstico normal no las ejecuta.

```text
Windows: .venv\Scripts\python.exe -m unittest discover -s tests -v
Linux:   .venv/bin/python -m unittest discover -s tests -v
```

Las pruebas conservan los trabajadores añadidos. Las fórmulas y los filtros del conjunto original mantienen sus referencias. Las evidencias nuevas están en `docs/evidencias/pruebas_mysql8.txt`, `migracion_mysql8.json` y `curl_resultados.json`. Las evidencias históricas se identifican como tales; no sustituyen la nueva comprobación en MySQL 8.

La colección Postman y los comandos curl están en `postman/`. El Word menciona SSMS; DataGrip es el cliente usado aquí para consultar el mismo SQL Server. La ejecución real se verifica en Linux; falta una comprobación en un equipo Windows real.

No distribuir `.env`, `.venv`, `.local` ni respaldos privados. El SQL de datos que se entrega es el conjunto de clase, no un volcado de tus registros personales nuevos.

## Versiones y licencias

AdminLTE 4.10.1, Bootstrap 5.3.8, Chart.js 4.5.1, Tabulator 6.6.1 y Bootstrap Icons 1.13.1, conservando las licencias en static/vendor/licencias. Python y bibliotecas instaladas se registran en requirements-lock.txt; requirements.txt define rangos compatibles. tzdata es condicional para Windows.

Referencia oficial de MySQL 8.4 y sus contenedores: https://dev.mysql.com/doc/refman/8.4/en/docker-mysql-getting-started.html

## Informe y pruebas completas

El informe final es breve (6 páginas), con capturas de dashboard, listado filtrado, CRUD, transferencia y pruebas curl. La verificación final reproducible usa bases de ensayo separadas con datos de clase: 19 pruebas, 104 comprobaciones adicionales y 21 peticiones curl. Ver docs/Pruebas_completas_Grupo3.md. Los números de la base operativa pueden cambiar; MERGE no sincroniza borrados ni elimina filas históricas del destino.
