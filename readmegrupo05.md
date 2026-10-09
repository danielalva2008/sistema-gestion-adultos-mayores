# Módulo de Habitaciones – Grupo 05

## Sistema de Gestión de Residencia para Adultos Mayores

Este módulo forma parte del **Sistema de Gestión de Residencia para Adultos Mayores (CIAM)**, desarrollado en el curso **Desarrollo de Plataformas**.

El objetivo de este módulo es administrar el inventario de habitaciones de la residencia, permitiendo registrar, consultar, actualizar y controlar el estado de cada una de ellas (Disponible, Ocupada o en Mantenimiento).

---

### Rama de trabajo

`dev/grupo3`

### Tabla principal

* `habitaciones`

### Integrantes del Grupo 03

* Robinson Cubas
* Jheral Lopez
* Jose Llatas
* Andire Chamba
* Deivy Bacalla
* Welly Vin

---

### Funcionalidades del módulo (CRUD)

| Operación  | Descripción                                                           |
| ---------- | --------------------------------------------------------------------- |
| **Create** | Registrar una nueva habitación (número, piso, capacidad y estado)     |
| **Read**   | Listar y filtrar habitaciones por estado o piso                       |
| **Update** | Actualizar datos y estado de una habitación                           |
| **Delete** | Eliminar una habitación solo si no tiene residentes activos asignados |

---

### Historias de usuario implementadas

* Como personal, quiero registrar una nueva habitación con número, piso y capacidad, para ampliar el inventario disponible.
* Como supervisor, quiero ver el listado de habitaciones con su estado (DISPONIBLE, OCUPADA, MANTENIMIENTO), para planificar el ingreso de nuevos residentes.
* Como personal, quiero actualizar el estado de una habitación, para reflejar trabajos de mantenimiento u ocupación.
* Como administrador, quiero eliminar una habitación solo si no tiene residentes activos asignados, para evitar inconsistencias en los datos.

---

### Reglas de negocio aplicadas

* Validación de `capacidad > 0` y `piso > 0`.
* Bloqueo de eliminación de habitaciones que tengan residentes activos.
* Respeto de las restricciones definidas en `database.sql` (CHECK, UNIQUE, llaves foráneas).

---

### Tecnologías utilizadas

* PHP 8.x
* MySQL 8.x (XAMPP)
* PDO / consultas preparadas
* Bootstrap (interfaz responsiva)

---

### Estructura del proyecto
