# Revisión contra el Word Grupo 3 Personal

Se revisaron las secciones comunes 3.2 a 3.7 y la sección 7 del Grupo 3.

- Carpeta principal: grupo3_personal.
- Archivos principales: app.py, config.py, db_mysql.py y db_sqlserver.py.
- Módulo: modulo/routes.py y modulo/transferencia.py.
- Destino: sql/sqlserver_personal.sql.
- Plantilla: dist de AdminLTE en static, con css, js y assets.
- Páginas: html/index3.html, html/tables/data.html y html/crud.html.
- Pruebas: postman/coleccion.json, con base_url http://127.0.0.1:5000.
- Configuración y dependencias: .env.example y requirements.txt.
- Los nueve endpoints exigidos en 7.4 existen y pertenecen únicamente a /api/personal.
- Los seis KPIs y las cinco comparaciones por cargo de 7.3 coinciden con el script.
- CRUD: código único, obligatorios, longitudes, correo, teléfono, fecha no futura y estados.
- Baja lógica: personal con actividades o incidentes no se elimina; devuelve 409.
- Filtros: cargo, estado, especialidad, fecha_desde, fecha_hasta y q.
- SQL Server: todas las columnas de personal más fecha_transferencia, sin IDENTITY y sin llaves foráneas. Verificado contra la tabla real.
- Transferencia: MERGE por ID, confirmación, carga, conteos y resumen; commit o rollback completo.
- JSON: listado con ok, total y data; errores con ok=false y error; DELETE 204 sin cuerpo. La transferencia usa el formato propio de 3.6.
- Consultas parametrizadas, usuarios de aplicación y secretos solo en .env, excluido del ZIP.

actividades, incidentes y residentes solo se consultan como tablas de apoyo. No se desarrollan sus módulos ni sus CRUD.

Los archivos complementarios de documentación, pruebas y configuración de VS Code se incluyen como apoyo. base.html comparte el menú y validacion.py concentra las validaciones; no sustituyen ni renombran los archivos exigidos.

## Configuración final MySQL 8

Origen real MySQL Community Server 8.4.13 en 127.0.0.1:3307. Se conservaron las 16 filas de Personal y todos los datos de las tablas de apoyo mediante copia y comparación de todos los campos. La base anterior permanece intacta y ya no es la conexión de la aplicación. SQL Server sigue independiente en 1433. La estructura, los seis KPIs, las nueve rutas, el CRUD, los recursos de AdminLTE y la portabilidad Windows/Linux se conservan. Windows real sigue pendiente de validación.
