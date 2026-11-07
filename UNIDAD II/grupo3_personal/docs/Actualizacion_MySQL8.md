# Configuración final conforme al motor de origen del Word

El proyecto utiliza MySQL Community Server 8.4.13, no depende de XAMPP. En esta computadora se reutilizó el servidor MySQL 8 existente en su contenedor y puerto 3307, sin tocar el SQL Server existente de 1433. Docker aloja el motor; no reemplaza Flask, MySQL, PyMySQL ni la estructura pedida. Otra computadora puede usar MySQL 8 instalado directamente.

Se copiaron los datos actuales mediante una transacción y se compararon todos los campos de las nueve tablas. Personal conserva 16 filas. No se borró ni se modificó el origen anterior. El respaldo privado y las configuraciones anteriores están en .local, fuera de la entrega.

Se mantienen los lanzadores de Windows y Linux, las conexiones independientes, el driver automático 17/18, las correcciones de fechas y fetch, los comentarios del código y toda la interfaz del Grupo 3. Las pruebas se adaptaron para conservar trabajadores añadidos sin exigir restaurar quince filas. El origen SQL original de clase sigue aportando las referencias de fórmulas y filtros. La aplicación no importa ni administra bases automáticamente.

