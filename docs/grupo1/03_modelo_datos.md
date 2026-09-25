# 03 — Modelo de datos

> **Proyecto:** Sistema de Gestión de Residencia para Adultos Mayores  
> **Grupo:** 1 — Usuarios y Roles  
> **Alcance confirmado por el docente:** CRUD de `usuarios`; `roles` funciona como catálogo para asignar un rol mediante un combo/lista desplegable. No se implementará CRUD de `roles`.

## 1. Fuente de verdad del modelo

La estructura técnica debe respetar el archivo oficial `database.sql`. No se deben crear, renombrar ni eliminar columnas, tablas, relaciones o restricciones por iniciativa del grupo sin una nueva indicación del docente.

Para interpretar el alcance del Grupo 1 se consideran, en este orden:

1. indicaciones directas del docente;
2. `database.sql`;
3. documento de formulación de la actividad;
4. repositorio oficial;
5. buenas prácticas técnicas, únicamente para completar aspectos de implementación que no cambien el alcance.

## 2. Base de datos

| Elemento | Valor |
|---|---|
| Base de datos | `sistema_residencia` |
| Motor de tablas | `InnoDB` |
| Juego de caracteres | `utf8mb4` |
| Collation | `utf8mb4_unicode_ci` |

El script inicia con `DROP DATABASE IF EXISTS sistema_residencia`, por lo que volver a ejecutarlo recrea la base y puede eliminar datos introducidos durante las pruebas. Debe considerarse un script de inicialización/reinicio del entorno académico.

## 3. Inventario real del esquema

`database.sql` crea **9 tablas**:

1. `roles`
2. `usuarios`
3. `habitaciones`
4. `residentes`
5. `personal`
6. `tipos_incidente`
7. `incidentes`
8. `actividades`
9. `participaciones`

También crea **2 vistas**:

- `vw_incidentes_residentes`
- `vw_participacion_actividades`

> **Observación:** el documento de formulación menciona “10 tablas”, pero enumera nueve y el SQL crea efectivamente nueve. Para el modelo técnico se toma como referencia lo que ejecuta `database.sql`.

## 4. Tablas relevantes para el Grupo 1

El Grupo 1 trabaja funcionalmente sobre `usuarios`. La tabla `roles` es una dependencia necesaria porque cada usuario debe tener un rol existente.

### 4.1 Tabla `roles`

Definición oficial:

```sql
CREATE TABLE roles (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(200),
    estado ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO'
) ENGINE=InnoDB;
```

| Columna | Tipo | NULL | Restricciones | Default | Interpretación |
|---|---|---:|---|---|---|
| `id_rol` | `INT` | No | `PRIMARY KEY`, `AUTO_INCREMENT` | automático | Identificador interno del rol |
| `nombre` | `VARCHAR(50)` | No | `UNIQUE` | — | Nombre único del rol |
| `descripcion` | `VARCHAR(200)` | Sí | — | `NULL` | Descripción opcional |
| `estado` | `ENUM('ACTIVO','INACTIVO')` | No | `ENUM` | `ACTIVO` | Estado del rol |

No existen `CHECK` definidos en esta tabla.

### 4.2 Tabla `usuarios`

Definición oficial:

```sql
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    id_rol INT NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    email VARCHAR(120) UNIQUE,
    estado ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_rol
        FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
) ENGINE=InnoDB;
```

| Columna | Tipo | NULL | Restricciones / relación | Default | Interpretación |
|---|---|---:|---|---|---|
| `id_usuario` | `INT` | No | `PRIMARY KEY`, `AUTO_INCREMENT` | automático | Identificador del usuario |
| `id_rol` | `INT` | No | `FOREIGN KEY` → `roles.id_rol` | — | Rol asignado al usuario |
| `username` | `VARCHAR(50)` | No | `UNIQUE` | — | Nombre de usuario único |
| `password_hash` | `VARCHAR(255)` | No | — | — | Hash de la contraseña |
| `nombres` | `VARCHAR(100)` | No | — | — | Nombres del usuario |
| `apellidos` | `VARCHAR(100)` | No | — | — | Apellidos del usuario |
| `email` | `VARCHAR(120)` | Sí | `UNIQUE` | `NULL` | Correo opcional; si existe no debe repetirse |
| `estado` | `ENUM('ACTIVO','INACTIVO')` | No | `ENUM` | `ACTIVO` | Estado de la cuenta |
| `fecha_creacion` | `TIMESTAMP` | No | — | `CURRENT_TIMESTAMP` | Fecha/hora de creación automática |

No existen `CHECK` definidos en esta tabla.

## 5. Claves y restricciones del Grupo 1

### Primary Keys

- `roles.id_rol`
- `usuarios.id_usuario`

Ambas son `AUTO_INCREMENT`; el formulario no debe pedir al usuario que introduzca esos identificadores.

### Foreign Key

```text
usuarios.id_rol  ─────►  roles.id_rol
```

La FK se llama `fk_usuario_rol` y obliga a que el `id_rol` guardado en `usuarios` exista previamente en `roles`.

### UNIQUE

- `roles.nombre`
- `usuarios.username`
- `usuarios.email`

`email` permite `NULL`; por ello el correo es opcional en el modelo SQL. La unicidad se aplica cuando se proporciona un valor.

### ENUM

- `roles.estado`: `ACTIVO`, `INACTIVO`
- `usuarios.estado`: `ACTIVO`, `INACTIVO`

### CHECK

No existen restricciones `CHECK` en `roles` ni en `usuarios`.

## 6. Relación `roles` — `usuarios`

La relación es **1:N**:

```text
ROLES                                  USUARIOS
┌──────────────┐                 ┌──────────────────┐
│ PK id_rol    │ 1            N  │ PK id_usuario    │
│ nombre       │────────────────<│ FK id_rol        │
│ descripcion  │                 │ username         │
│ estado       │                 │ password_hash    │
└──────────────┘                 │ nombres          │
                                 │ apellidos        │
                                 │ email            │
                                 │ estado           │
                                 │ fecha_creacion   │
                                 └──────────────────┘
```

Interpretación:

- un rol puede estar asociado con cero, uno o muchos usuarios;
- cada usuario debe tener exactamente un `id_rol` porque `usuarios.id_rol` es `NOT NULL`;
- el usuario guarda el ID del rol, no el texto `ADMINISTRADOR`, `SUPERVISOR` o `PERSONAL`.

## 7. Uso de `roles` confirmado por el docente

El docente confirmó que:

- el proyecto del Grupo 1 implementa **solamente el CRUD de usuarios**;
- cada usuario tiene un rol;
- los roles deben estar registrados en la base de datos;
- en el formulario de registro debe aparecer un **combo/lista desplegable** con los roles;
- se asigna uno de esos roles al usuario;
- **no se implementará CRUD de roles**.

Consecuencia de diseño: el formulario debe consultar la tabla `roles` para construir el selector. No se deben escribir los nombres de rol como una lista independiente y duplicada dentro del código PHP.

## 8. Datos de prueba relevantes

### Roles iniciales

`database.sql` inserta:

| `id_rol` esperado tras una importación limpia | `nombre` | `descripcion` | `estado` por default |
|---:|---|---|---|
| 1 | `ADMINISTRADOR` | Acceso completo al sistema | `ACTIVO` |
| 2 | `SUPERVISOR` | Supervisa residentes, actividades e incidentes | `ACTIVO` |
| 3 | `PERSONAL` | Gestiona las operaciones asignadas | `ACTIVO` |

### Usuario inicial

Se inserta un usuario de aplicación:

| Campo | Valor |
|---|---|
| `id_rol` | `1` |
| `username` | `admin` |
| `nombres` | `Administrador` |
| `apellidos` | `Sistema` |
| `email` | `admin@residencia.local` |
| `estado` | `ACTIVO` por default |
| `fecha_creacion` | automática |

El SQL comenta que la clave académica es `Admin123*`, pero el hash incluido en el script **no verifica contra esa contraseña**. Se registra como incidencia del script; no debe corregirse silenciosamente.

### Cantidades declaradas en los `INSERT`

| Tabla | Tuplas incluidas en el script |
|---|---:|
| `roles` | 3 |
| `usuarios` | 1 |
| `habitaciones` | 15 |
| `personal` | 15 |
| `residentes` | 20 |
| `tipos_incidente` | 8 |
| `actividades` | 12 |
| `participaciones` | 31 |
| `incidentes` | 12 intentadas |

> El `INSERT` de `incidentes` contiene una fila con un valor faltante y el statement completo no puede ejecutarse correctamente tal como está escrito. No se debe inventar el dato faltante.

## 9. Usuario MySQL de la aplicación

`database.sql` crea:

```text
Usuario MySQL: residencia_app
Host:          localhost
```

Y le concede sobre `sistema_residencia.*`:

- `SELECT`
- `INSERT`
- `UPDATE`
- `DELETE`

No se le conceden privilegios de administración del esquema como `CREATE`, `ALTER`, `DROP`, `CREATE USER` o `GRANT`.

Esto debe distinguirse del registro `admin` de la tabla `usuarios`:

```text
residencia_app  → cuenta técnica PHP → MySQL
admin           → registro de la tabla usuarios
```

## 10. Elementos que NO existen en el modelo

No aparecen en `database.sql`:

- `id_personal` dentro de `usuarios`;
- una FK entre `usuarios` y `personal`;
- tabla `permisos`;
- tabla `rol_permiso`;
- relación muchos-a-muchos usuario–rol;
- `telefono`, `dni` o `direccion` en `usuarios`;
- `fecha_modificacion`;
- `ultimo_login`.

El Grupo 1 no debe inventar estos campos ni relaciones.

## 11. Incidencias del script oficial

| ID | Hallazgo | Tratamiento |
|---|---|---|
| `INC-BD-01` | El documento de actividad menciona 10 tablas; el SQL crea 9 | Documentar; el esquema técnico sigue el SQL |
| `INC-BD-02` | El documento menciona 30 participaciones; el SQL contiene 31 tuplas | Documentar; no afecta Grupo 1 |
| `INC-BD-03` | Una fila del `INSERT INTO incidentes` tiene 9 valores para 10 columnas | No inventar el dato faltante; escalar al docente si se requiere corregir el SQL |
| `INC-BD-04` | El hash del usuario `admin` no verifica con la clave comentada `Admin123*` | Documentar; no corregir silenciosamente |

## 12. Aspectos que el Grupo 1 no debe rediseñar

Mientras el docente no cambie el esquema, se respetarán:

- nombres de tablas y columnas;
- tipos de datos;
- PK y FK;
- `UNIQUE`;
- `ENUM`;
- `NULL` / `NOT NULL`;
- `AUTO_INCREMENT`;
- defaults;
- relación 1:N entre `roles` y `usuarios`;
- uso del usuario MySQL `residencia_app`;
- inexistencia de CRUD de roles en el alcance del Grupo 1.

