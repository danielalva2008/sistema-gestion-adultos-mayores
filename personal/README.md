# Sistema de Gestión para Residencia de Adultos Mayores

## Módulo de Gestión de Personal — Grupo 4

**Curso:** Desarrollo de Plataformas Web  
**Proyecto:** Sistema de Gestión para Residencia de Adultos Mayores  
**Módulo asignado:** Gestión de Personal  
**Rama de desarrollo:** `feature/grupo-4-personal`

### Integrantes

- Santillán Valle Jhon Kelvin
- Salazar Salazar Luis Grimaldo
- María Carmen Tuesta Chuquizuta
- Ramos Ocampo Kevin

---

## 1. Resumen ejecutivo

El presente trabajo desarrolla el módulo de Gestión de Personal del Sistema de Gestión para una Residencia de Adultos Mayores. Su finalidad es centralizar la información laboral y de contacto de los colaboradores, facilitar su consulta y actualización, y conservar el historial institucional cuando un trabajador deja de prestar servicios.

La solución implementa las operaciones de registro, listado, búsqueda, edición e inactivación de personal. La inactivación se realiza mediante una baja lógica: el registro cambia al estado INACTIVO y permanece en la base de datos. Esta decisión protege la trazabilidad de las actividades e incidencias asociadas al trabajador y evita la pérdida de información histórica.

El módulo fue desarrollado con PHP 8, PDO, MySQL/MariaDB, HTML5 y CSS3. También incorpora validaciones en el servidor, consultas preparadas, protección CSRF, control de códigos duplicados y una interfaz adaptable a computadoras y dispositivos móviles.

## 2. Problemática

La residencia necesita mantener información confiable sobre las personas que participan en su operación diaria. Un registro manual o disperso dificulta localizar a un colaborador, conocer su cargo y especialidad, actualizar sus datos o determinar si continúa activo.

Además, eliminar físicamente a un trabajador podría dejar sin referencia las actividades que tuvo asignadas y los incidentes que reportó. Por ello, el sistema requiere una solución que permita administrar el directorio del personal sin comprometer la integridad del historial institucional.

## 3. Objetivos

### 3.1 Objetivo general

Desarrollar un módulo web seguro y funcional para administrar la información del personal de la residencia, garantizando la integridad de los datos y la conservación de sus relaciones históricas.

### 3.2 Objetivos específicos

- Registrar colaboradores con código, nombres, apellidos, cargo, especialidad y datos de contacto.
- Consultar el directorio mediante búsqueda y filtrado por estado.
- Actualizar la información de un trabajador existente.
- Inactivar personal mediante baja lógica, sin eliminar registros relacionados.
- Prevenir códigos duplicados y datos con formato inválido.
- Proteger las operaciones mediante consultas preparadas y token CSRF.
- Verificar el funcionamiento mediante pruebas integrales del flujo CRUD.

## 4. Alcance del módulo

La solución cubre el ciclo de administración del personal:

1. Registro de un nuevo colaborador.
2. Visualización del directorio completo.
3. Búsqueda por código, nombre, cargo o especialidad.
4. Filtrado de colaboradores activos e inactivos.
5. Edición de información personal, laboral y de contacto.
6. Consulta del impacto de una baja sobre actividades e incidentes.
7. Inactivación del registro con conservación del historial.

La autenticación general del sistema y la administración global de permisos corresponden al módulo de Usuarios. El módulo de Personal queda preparado para integrarse con ese mecanismo común.

## 5. Requisitos funcionales implementados

| Código | Requisito | Resultado |
| --- | --- | --- |
| RF-01 | Registrar personal con información laboral y de contacto | Implementado |
| RF-02 | Listar los registros del personal | Implementado |
| RF-03 | Buscar por código, nombre, cargo o especialidad | Implementado |
| RF-04 | Filtrar por estado ACTIVO o INACTIVO | Implementado |
| RF-05 | Editar los datos del colaborador | Implementado |
| RF-06 | Evitar el registro de códigos duplicados | Implementado |
| RF-07 | Inactivar personal sin eliminarlo de la base de datos | Implementado |
| RF-08 | Mostrar las relaciones afectadas antes de la baja | Implementado |
| RF-09 | Conservar actividades e incidentes relacionados | Implementado |
| RF-10 | Presentar una interfaz adaptable | Implementado |

## 6. Historias de usuario atendidas

- **HU-01 — Registro:** Como administrador, quiero registrar los datos de un colaborador para mantener actualizado el directorio institucional.
- **HU-02 — Consulta:** Como supervisor, quiero buscar personal por cargo o especialidad para identificar rápidamente al responsable adecuado.
- **HU-03 — Actualización:** Como administrador, quiero editar la información de un trabajador para corregir o actualizar sus datos.
- **HU-04 — Baja lógica:** Como administrador, quiero inactivar a un colaborador para reflejar que ya no labora en la residencia sin perder su historial.

## 7. Diseño técnico

El módulo separa la conexión, la lógica de negocio, el control de solicitudes y la presentación visual para facilitar su mantenimiento.

| Componente | Responsabilidad |
| --- | --- |
| `config.php` | Establece la conexión PDO con la base de datos |
| `model.php` | Contiene consultas preparadas, reglas y validaciones |
| `index.php` | Gestiona solicitudes, sesión, mensajes y token CSRF |
| `view.php` | Construye formularios, filtros y listado de personal |
| `style.css` | Define la presentación y el diseño adaptable |
| `database-inicial.sql` | Prepara el esquema y los datos académicos |
| `test.php` | Ejecuta pruebas integrales con reversión de cambios |

### 7.1 Modelo de datos

La entidad principal es la tabla `personal`. Cada trabajador se identifica mediante un código único y conserva sus datos personales, cargo, especialidad, teléfono, correo, fecha de ingreso y estado.

El registro se relaciona con `actividades.id_personal_responsable` e `incidentes.id_personal_reporta`. Debido a estas relaciones, la baja se implementó como un cambio de estado a INACTIVO. No se utiliza eliminación física, con lo cual las actividades y los incidentes mantienen su referencia original.

### 7.2 Seguridad e integridad

- Uso de PDO con consultas parametrizadas para reducir el riesgo de inyección SQL.
- Validación de campos obligatorios y longitudes permitidas.
- Verificación del formato de correo y teléfono.
- Control de fecha de ingreso y valores permitidos para el estado.
- Verificación previa y restricción única para el código del trabajador.
- Token CSRF en las operaciones que modifican información.
- Escape de contenido al presentar datos en la interfaz.
- Cuenta de aplicación separada para las operaciones ordinarias de la base de datos.

## 8. Resultados obtenidos

El módulo permite completar el flujo CRUD previsto para la entidad Personal. El usuario puede registrar trabajadores, consultar y filtrar el directorio, modificar datos existentes e inactivar colaboradores. Antes de confirmar una baja, el sistema informa la cantidad de actividades e incidentes asociados. Después de la operación, el trabajador continúa visible bajo el estado INACTIVO y sus relaciones permanecen intactas.

La interfaz presenta mensajes claros para operaciones exitosas y errores de validación. El diseño se adapta a pantallas pequeñas y conserva el acceso a la información mediante desplazamiento horizontal controlado en la tabla.

## 9. Verificación y pruebas

Las pruebas se ejecutaron con PHP 8.2.12 y MariaDB 10.4.32, utilizando datos académicos en un entorno local aislado.

| Prueba | Resultado esperado | Estado |
| --- | --- | --- |
| Registro válido | El colaborador aparece en el listado | Conforme |
| Código duplicado | El sistema rechaza el registro | Conforme |
| Búsqueda y filtrado | Solo se muestran coincidencias | Conforme |
| Edición | Los cambios quedan almacenados | Conforme |
| Correo o teléfono inválido | Se muestra un mensaje de validación | Conforme |
| Consulta de impacto | Se informan las relaciones existentes | Conforme |
| Baja lógica | El estado cambia a INACTIVO | Conforme |
| Conservación de relaciones | Actividades e incidentes mantienen su referencia | Conforme |
| Consultas parametrizadas | Los valores se procesan mediante PDO | Conforme |

El archivo `test.php` automatiza las verificaciones de creación, lectura, actualización, búsqueda, duplicados, formatos, longitudes, estados y conservación de referencias. Las escrituras de prueba se revierten mediante una transacción.

## 10. Evidencias del funcionamiento

Las siguientes capturas corresponden a una demostración local con información ficticia.

### 10.1 Directorio y búsqueda

![Listado y búsqueda de personal](capturas/01-listado.png)

### 10.2 Registro de personal

![Formulario de registro](capturas/02-registro.png)

### 10.3 Actualización de información

![Edición de un trabajador](capturas/03-edicion.png)

### 10.4 Evaluación del impacto de la baja

![Confirmación de baja y relaciones](capturas/04-confirmacion-baja.png)

### 10.5 Conservación del registro inactivo

![Trabajador conservado como inactivo](capturas/05-baja-realizada.png)

### 10.6 Interfaz adaptable

![Vista del módulo en pantalla pequeña](capturas/06-movil.png)

## 11. Conclusiones

- Se completó el módulo de Gestión de Personal de acuerdo con las funciones asignadas al Grupo 4.
- La baja lógica conserva el historial y evita romper las relaciones con actividades e incidentes.
- Las reglas de validación y las consultas preparadas mejoran la calidad y seguridad de la información.
- La separación de componentes facilita el mantenimiento y la integración con el resto del sistema.
- Las pruebas realizadas confirman el funcionamiento de las operaciones principales y de los casos de validación.

## 12. Anexo técnico: ejecución local

### Requisitos

- PHP 8.x con las extensiones PDO MySQL y mbstring.
- MySQL 8 o MariaDB compatible.
- Navegador web actualizado.

### Ejecución rápida para demostración

Desde la raíz del repositorio:

~~~bash
bash personal/dev.sh
~~~

Luego abrir [http://127.0.0.1:8084](http://127.0.0.1:8084). El entorno local se guarda en `personal/.local/`, carpeta excluida de Git.

También puede iniciarse desde Visual Studio Code mediante **Terminal → Run Task → Personal: iniciar entorno local**.

### Ejecución de pruebas

Con la demostración detenida:

~~~bash
bash personal/dev.sh test
~~~

También puede usarse la tarea **Personal: ejecutar pruebas** de Visual Studio Code.

### Instalación con la base académica

El archivo `personal/database-inicial.sql` prepara una instalación nueva. No debe importarse sobre una base que contenga información que se necesite conservar.

~~~bash
/opt/lampp/bin/mysql -u root -p < personal/database-inicial.sql
/opt/lampp/bin/php -S 127.0.0.1:8084 -t personal personal/router.php
~~~

La configuración admite las variables `PERSONAL_DB_HOST`, `PERSONAL_DB_PORT`, `PERSONAL_DB_NAME`, `PERSONAL_DB_USER`, `PERSONAL_DB_PASSWORD` y `PERSONAL_DB_SOCKET`, de modo que la conexión pueda ajustarse sin modificar el código compartido.
