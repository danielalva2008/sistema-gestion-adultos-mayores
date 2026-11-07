-- MySQL 8: ejecutar MANUALMENTE como administrador solo si residencia_app ya existe.
-- Ejemplo para conexión local: ajustar el host al usuario real de ese servidor.
-- No crea usuarios ni cambia contraseñas; el arranque nunca ejecuta este archivo.
GRANT SELECT ON sistema_residencia.* TO 'residencia_app'@'localhost';
GRANT INSERT, UPDATE, DELETE ON sistema_residencia.personal TO 'residencia_app'@'localhost';
