# Guía de archivos del módulo de Personal

Abre `personal/` en Visual Studio Code. Para encontrar el código según la tarea:

| Si quieres... | Archivo que debes abrir |
| --- | --- |
| Cambiar los campos, botones, etiquetas o la tabla de personal | `views/personal_view.php` |
| Cambiar cómo se ve el módulo | `public/css/style.css` |
| Cambiar qué ocurre al editar, guardar o inactivar | `controllers/personal_controller.php` |
| Cambiar la validación, el guardado o la búsqueda en la base de datos | `models/personal_model.php` |
| Cambiar los campos o tablas de la base de datos | `database/database.sql` |
| Cambiar los datos para conectarse a MySQL | `config/database.php` |
| Abrir el módulo | `index.php` |

**Ejemplo: editar personal.** El formulario está en `views/personal_view.php`; el controlador recibe la acción y carga al trabajador; el modelo valida y guarda los cambios. Los archivos `editar.php`, `guardar.php` y `actualizar.php` son accesos de compatibilidad: normalmente no necesitas modificarlos.

**Ejemplo: cambiar las búsquedas.** El formulario y sus textos están en `views/personal_view.php`; el controlador recibe los filtros; la consulta que busca por nombre, código, cargo o especialidad está en `models/personal_model.php`, en `personal_list()`.
