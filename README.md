# Grupo 3 · CRUD de habitaciones

Módulo del Sistema de Gestión de Residencia para Adultos Mayores, curso Desarrollo de Plataformas. Permite crear, consultar, actualizar y eliminar habitaciones con las validaciones del documento del docente.

## Integrantes

Completar antes de entregar: 1. __________ · 2. __________ · 3. __________ · 4. __________ · 5. __________ · 6. __________.

## Abrir en este equipo

La copia revisada está en la carpeta actual de este repositorio. Inicia Apache y MySQL de XAMPP y publica **esta copia** en `htdocs/grupo-3-habitaciones` para abrir http://localhost/grupo-3-habitaciones/. Si ya existe otra copia en XAMPP, comprueba que contiene estos cambios: las carpetas no se sincronizan automáticamente.

No uses Live Server: PHP necesita ejecutarse mediante PHP/Apache. La configuración privada está en `config/database.local.php` y no debe entregarse.

## Instalar en otro equipo

1. Instalar/iniciar XAMPP con Apache y MySQL/MariaDB, PHP 8.x y extensiones PDO MySQL y mbstring.
2. Copiar la carpeta `grupo-3-habitaciones` completa a `htdocs` (Windows: `C:\xampp\htdocs`; Linux: `/opt/lampp/htdocs`).
3. En phpMyAdmin, importar la base oficial corregida `database/sistema_residencia.sql` **solo en una instalación nueva**. Ese archivo contiene `DROP DATABASE IF EXISTS sistema_residencia`: reimportarlo borra la base anterior. El módulo no ejecuta importaciones ni necesita cambiar el esquema. Se usa el archivo suministrado por el curso, no una base alternativa.
4. Copiar `config/database.example.php` como `config/database.local.php`. Completar la contraseña indicada en el SQL del docente para el usuario `residencia_app`, host, puerto y nombre de base. No usar `root` en el CRUD. La configuración local se excluye de Git y del ZIP para entregar.
5. Abrir http://localhost/grupo-3-habitaciones/.

Probado con el XAMPP de este equipo: PHP 8.2.12 y MariaDB 10.4.32. El documento menciona MySQL 8.x; no se ha ejecutado una prueba en un servidor MySQL 8 independiente. XAMPP instalado utiliza MariaDB y el esquema existente es compatible con este módulo.

## Alcance y tablas

- `habitaciones`: CRUD completo sobre `id_habitacion`, `numero`, `piso`, `capacidad`, `estado`, `observaciones`.
- `residentes`: consulta de cantidad y estado para calcular ocupación y proteger la eliminación. No hay formularios de residentes.
- Al eliminar una habitación sin residentes activos, la FK original aplica `ON DELETE SET NULL` a las asignaciones de residentes inactivos o egresados. Sus fichas se conservan.

El alcance es exclusivamente el grupo 3. No incluye autenticación ni administración de usuarios del grupo 1. Las historias mencionan personal, supervisor y administrador como roles del sistema integrado; esta demostración local todavía no impone permisos por rol. `.htaccess` limita el acceso a localhost. Al integrar, conectar las rutas con la autenticación y los permisos acordados con el grupo 1.

## Historias de usuario implementadas

| Historia | Función |
| --- | --- |
| Como personal, quiero registrar una habitación con número, piso y capacidad para ampliar el inventario. | Nueva habitación y guardado validado. |
| Como supervisor, quiero consultar habitaciones y su estado para planificar ingresos. | Listado con búsqueda por número, filtro de estado y filtro de piso. |
| Como personal, quiero actualizar el estado para reflejar mantenimiento u ocupación. | Edición precargada de los datos y los tres estados. |
| Como administrador, quiero eliminar solo habitaciones sin residentes activos para evitar inconsistencias. | Confirmación y validación en servidor antes del DELETE. |

## Reglas y decisiones

- Número obligatorio, único, de hasta 10 caracteres; permite códigos alfanuméricos porque el campo original es VARCHAR.
- Piso y capacidad: enteros de 1 a 2147483647; validación en navegador, PHP y restricciones de la base.
- Estado: DISPONIBLE, OCUPADA o MANTENIMIENTO.
- Observaciones: opcionales, hasta 255 caracteres.
- Bloqueo de eliminación si `estado = OCUPADA` **o** existe al menos un residente ACTIVO asignado. Se comprueba de nuevo al enviar el formulario.
- Validación adicional: al editar no se permite una capacidad inferior a la cantidad de residentes activos. Conviene presentar esta ampliación al docente.
- Los datos originales de las habitaciones 303 y 305 tienen capacidad 1 y 2 residentes activos. Se muestran advertencias; el módulo no los corrige automáticamente.
- El estado es manual, como indica la historia del grupo 3. Los contadores del encabezado cuentan el estado registrado; la ocupación se calcula consultando residentes y no se guarda en una columna duplicada.
- Las consultas con valores variables usan parámetros PDO. Los fragmentos de filtros añadidos al SQL son constantes, nunca entrada del usuario.
- Actualización y eliminación usan transacciones y bloqueos de filas para revisar la ocupación antes de escribir. El módulo de residentes deberá respetar capacidad y estados al integrarse; este módulo no sustituye esas validaciones.
- Los formularios modifican datos solo mediante POST y token CSRF. El HTML escapa la salida y muestra mensajes de éxito/error.

## Organización del código

```text
index.php                       Redirección al listado
config/Conexion.php             Conexión PDO
config/database.example.php     Plantilla de conexión
config/database.local.php       Configuración privada de cada equipo
modelos/Habitacion.php           Consultas y reglas de negocio
controladores/habitaciones.php  Lectura de solicitudes y coordinación
vistas/habitaciones.php         Listado, formulario y confirmación
includes/                       Sesión, utilidades, cabecera y pie
public/estilos.css               Diseño adaptable, sin CDN
tests/integracion.php           Pruebas de modelo en tablas temporales
database/sistema_residencia.sql Base de datos oficial corregida
docs/                          Capturas del módulo
```

Se tomó como referencia la separación en configuración, modelos y vistas del sistema de asistencia. Este módulo usa formularios PHP tradicionales: no requiere AJAX, jQuery, Composer, npm ni conexión a Internet.

## Verificación realizada

Revisión del 24 de septiembre de 2026 sobre esta copia:

- Sintaxis correcta en todos los archivos PHP.
- 25 comprobaciones automatizadas del modelo aprobadas sobre tablas temporales: CRUD, filtros, duplicados, números inválidos, estados, longitudes, ocupación y rollback.
- Conexión PDO de la aplicación comprobada contra la base local; UNIQUE, CHECK y FK contrastados con el SQL del docente y el esquema instalado.
- Pruebas HTTP: listado, formulario de creación, búsqueda sin resultados y rechazo de CSRF inválido (403), sin modificar registros persistentes.
- Corregida la conexión incompatible que dependía de `global.php` y usaba mysqli; ahora expone `conexion(): PDO` y carga la configuración privada.
- Corregida la conservación y visualización de observaciones con texto `0`.
- Las capturas existentes son referencias previas. Pendiente repetir la revisión visual y la demostración completa en el equipo de entrega, incluida la adaptación móvil.

Ejecutar las pruebas del modelo desde la carpeta del proyecto en Linux:

```bash
/opt/lampp/bin/php tests/integracion.php
```

En Windows: `C:\xampp\php\php.exe tests\integracion.php`. Estas pruebas de desarrollo usan `root` únicamente para crear tablas **TEMPORARY** en una conexión CLI; no modifican las tablas persistentes ni la conexión del CRUD. Se pueden configurar `TEST_DB_USER` y `TEST_DB_PASSWORD`. Las copias temporales conservan columnas, índices y CHECK, pero excluyen FK; estas pruebas no validan cascadas ni simulan concurrencia.

## Capturas

![Listado de habitaciones](docs/listado.png)

![Edición de una habitación](docs/editar.png)

## Integración con Git y GitHub

El módulo del grupo 3 se mantiene en la rama `dev/grupo3` del [repositorio del curso](https://github.com/danielalva2008/sistema-gestion-adultos-mayores/tree/dev/grupo3).

La base de datos oficial corregida está en [`database/sistema_residencia.sql`](database/sistema_residencia.sql). La configuración privada `config/database.local.php` se excluye de Git.

Queda pendiente completar los integrantes y acordar la integración de autenticación y permisos con el equipo.
