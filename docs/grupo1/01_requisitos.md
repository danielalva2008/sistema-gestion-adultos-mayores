# 01 — Requisitos funcionales — Grupo 1

> **Proyecto:** Sistema de Gestión de Residencia para Adultos Mayores  
> **Grupo:** 1 — Usuarios y Roles  
> **Módulo entregable:** CRUD de `usuarios`  
> **Tabla auxiliar:** `roles` como catálogo  
> **Estado:** BASELINE FUNCIONAL V1 — lista para implementación  
> **Importante:** este documento no autoriza login, permisos ni CRUD de `roles`.

---

## 1. Fuentes y prioridad

Para resolver contradicciones o dudas se usará este orden:

1. indicación directa más reciente del docente;
2. `database.sql`;
3. documento oficial de formulación;
4. decisiones vigentes del Grupo 1;
5. buenas prácticas que no modifiquen el alcance ni el modelo.

### Clasificación usada en este documento

- **DOCENTE:** requisito indicado por el profesor o el documento oficial.
- **SQL:** obligación impuesta por `database.sql`.
- **DOCENTE + SQL:** ambas fuentes coinciden.
- **DECISIÓN DEL GRUPO:** comportamiento no fijado por el profesor ni por el SQL, elegido para completar el CRUD.
- **BUENA PRÁCTICA:** medida técnica que no altera el alcance.

---

## 2. Alcance funcional definitivo

### 2.1 Dentro del alcance

| ID | Requisito funcional | Fuente |
|---|---|---|
| `RF-01` | Listar usuarios. | DOCENTE |
| `RF-02` | Buscar usuarios por nombres, apellidos o `username`. | DOCENTE + DECISIÓN UX |
| `RF-03` | Filtrar usuarios por estado `ACTIVO` o `INACTIVO`. | DOCENTE |
| `RF-04` | Registrar usuarios. | DOCENTE |
| `RF-05` | Asignar exactamente un rol existente a cada usuario. | DOCENTE + SQL |
| `RF-06` | Cargar el selector de roles desde la tabla `roles`. | DOCENTE + SQL |
| `RF-07` | Editar datos de un usuario. | DOCENTE |
| `RF-08` | Cambiar el rol de un usuario. | DOCENTE |
| `RF-09` | Cambiar el estado de un usuario. | DOCENTE |
| `RF-10` | Inactivar usuarios mediante baja lógica. | DOCENTE |
| `RF-11` | Reactivar usuarios inactivos. | DECISIÓN DEL GRUPO |
| `RF-12` | Permitir cambio opcional de contraseña durante la edición. | DECISIÓN DEL GRUPO |
| `RF-13` | Validar restricciones antes de insertar o actualizar. | SQL + BUENA PRÁCTICA |
| `RF-14` | Mostrar mensajes claros de éxito, validación y error. | DOCENTE |
| `RF-15` | Mostrar el nombre del rol en la interfaz y guardar `id_rol`. | DOCENTE + SQL |

### 2.2 Fuera del alcance

No se implementará:

- CRUD de `roles`;
- login;
- logout;
- sesiones de autenticación;
- autorización o permisos por rol;
- recuperación de contraseña;
- bloqueo por intentos fallidos;
- auditoría de login;
- relación `usuarios`–`personal`;
- relación entre `usuarios` e incidentes/actividades que no existe en el SQL;
- borrado físico como operación normal de baja;
- campos no existentes en `database.sql`.

---

## 3. Actor funcional

El documento del profesor redacta las historias del Grupo 1 usando el actor **administrador**.

En esta versión del proyecto, `administrador` es un **actor conceptual de la historia**, no un usuario autenticado cuya identidad deba comprobarse.

**Motivo:** el docente confirmó que login, sesiones y permisos quedan fuera del alcance.

---

## 4. Datos permitidos por el modelo

### 4.1 Tabla `usuarios`

| Campo SQL | Uso funcional |
|---|---|
| `id_usuario` | Identificador automático. No se solicita en formularios. |
| `id_rol` | Rol asignado al usuario. |
| `username` | Nombre de usuario. |
| `password_hash` | Hash de la contraseña. Nunca se muestra como contraseña editable. |
| `nombres` | Nombres del usuario. |
| `apellidos` | Apellidos del usuario. |
| `email` | Correo opcional. |
| `estado` | `ACTIVO` o `INACTIVO`. |
| `fecha_creacion` | Generada automáticamente por MySQL. |

### 4.2 Tabla `roles`

El módulo solo la consulta como catálogo.

Campos relevantes:

- `id_rol`;
- `nombre`;
- `estado`.

La aplicación guarda `usuarios.id_rol` y muestra al usuario el valor comprensible de `roles.nombre`.

---

# 5. Historias de usuario finales

## HU-01 — Registrar usuario

> Como administrador, quiero registrar un nuevo usuario indicando su rol, para mantener las cuentas del sistema correctamente registradas y asociadas a un rol.

**Fuente:** historia del docente, ajustando el beneficio al alcance confirmado.

### Criterios de aceptación HU-01

| ID | Criterio |
|---|---|
| `CA-01.1` | El formulario muestra nombres, apellidos, email, username, contraseña y rol. |
| `CA-01.2` | Nombres, apellidos, username, contraseña y rol son obligatorios. |
| `CA-01.3` | El email puede quedar vacío. |
| `CA-01.4` | Si se proporciona email, debe tener formato válido. |
| `CA-01.5` | Se rechaza un `username` ya registrado. |
| `CA-01.6` | Se rechaza un email ya registrado. |
| `CA-01.7` | El rol recibido debe existir y estar permitido para asignación. |
| `CA-01.8` | La contraseña se transforma a hash antes de almacenarse. |
| `CA-01.9` | Un usuario nuevo se registra con estado `ACTIVO`. |
| `CA-01.10` | `fecha_creacion` la genera MySQL. |
| `CA-01.11` | Tras un registro correcto se muestra un mensaje de éxito. |
| `CA-01.12` | El nuevo usuario aparece luego en el listado. |

---

## HU-02 — Listar y buscar usuarios

> Como administrador, quiero listar y buscar usuarios por nombre, username o estado, para ubicarlos rápidamente.

**Fuente:** DOCENTE.

### Criterios de aceptación HU-02

| ID | Criterio |
|---|---|
| `CA-02.1` | Al entrar al módulo se muestran todos los usuarios. |
| `CA-02.2` | Se puede buscar por `nombres`. |
| `CA-02.3` | Se puede buscar por `apellidos`. |
| `CA-02.4` | Se puede buscar por `username`. |
| `CA-02.5` | Se puede filtrar por `ACTIVO`. |
| `CA-02.6` | Se puede filtrar por `INACTIVO`. |
| `CA-02.7` | El texto de búsqueda y el filtro de estado pueden combinarse. |
| `CA-02.8` | Si no existen coincidencias se muestra un mensaje comprensible. |
| `CA-02.9` | El listado muestra el nombre del rol, no solamente `id_rol`. |

---

## HU-03 — Editar usuario

> Como administrador, quiero editar los datos de un usuario y cambiar su rol o estado, para mantener su información actualizada.

**Fuente:** DOCENTE.

### Criterios de aceptación HU-03

| ID | Criterio |
|---|---|
| `CA-03.1` | La pantalla de edición precarga los datos actuales del usuario. |
| `CA-03.2` | El `username` puede modificarse. |
| `CA-03.3` | Username y email conservan sus reglas de unicidad, excluyendo al propio usuario editado. |
| `CA-03.4` | Se puede cambiar a otro rol permitido. |
| `CA-03.5` | Se puede cambiar el estado entre `ACTIVO` e `INACTIVO`. |
| `CA-03.6` | Un usuario inactivo puede reactivarse. |
| `CA-03.7` | Dejar vacía la nueva contraseña conserva el `password_hash` existente. |
| `CA-03.8` | Introducir nueva contraseña genera un hash nuevo. |
| `CA-03.9` | El hash almacenado nunca se precarga como contraseña en el formulario. |
| `CA-03.10` | Los datos modificados deben respetar longitudes, formatos y restricciones SQL. |
| `CA-03.11` | Tras actualizar correctamente se muestra un mensaje de éxito. |

---

## HU-04 — Inactivar usuario

> Como administrador, quiero inactivar un usuario mediante baja lógica en lugar de eliminarlo físicamente, para conservar el registro de la cuenta.

**Fuente:** historia del docente, ajustando el beneficio al modelo real.

### Criterios de aceptación HU-04

| ID | Criterio |
|---|---|
| `CA-04.1` | Un usuario `ACTIVO` puede pasar a `INACTIVO`. |
| `CA-04.2` | La baja se realiza mediante actualización de `estado`. |
| `CA-04.3` | El registro continúa existiendo en la tabla `usuarios`. |
| `CA-04.4` | No se usa `DELETE FROM usuarios` como operación normal del CRUD. |
| `CA-04.5` | Se muestra el resultado de la operación mediante un mensaje claro. |
| `CA-04.6` | El listado refleja el nuevo estado. |

---

# 6. Pantallas

## 6.1 Pantalla — Listar usuarios

### Controles

- campo de búsqueda por nombres, apellidos o username;
- filtro `TODOS / ACTIVO / INACTIVO`;
- botón para registrar un nuevo usuario.

### Columnas visibles recomendadas

| Columna | Origen |
|---|---|
| Username | `usuarios.username` |
| Nombres | `usuarios.nombres` |
| Apellidos | `usuarios.apellidos` |
| Email | `usuarios.email` |
| Rol | `roles.nombre` |
| Estado | `usuarios.estado` |
| Fecha de creación | `usuarios.fecha_creacion` |
| Acciones | elemento de interfaz |

### Acciones

Para un usuario activo:

- Editar;
- Inactivar.

Para un usuario inactivo:

- Editar;
- Reactivar.

No se mostrará `password_hash`.

---

## 6.2 Pantalla — Registrar usuario

| Campo visual | Campo/resultado SQL | Obligatorio |
|---|---|---:|
| Nombres | `nombres` | Sí |
| Apellidos | `apellidos` | Sí |
| Email | `email` | No |
| Username | `username` | Sí |
| Contraseña | genera `password_hash` | Sí |
| Rol | guarda `id_rol` | Sí |

No se solicitan:

- `id_usuario`;
- `estado`;
- `fecha_creacion`.

`id_usuario` es automático, `estado` usa el valor por defecto `ACTIVO` y `fecha_creacion` usa `CURRENT_TIMESTAMP`.

---

## 6.3 Pantalla — Editar usuario

| Campo visual | Comportamiento |
|---|---|
| Nombres | precargado y editable |
| Apellidos | precargado y editable |
| Email | precargado, editable y opcional |
| Username | precargado y editable |
| Nueva contraseña | vacía = conservar contraseña actual |
| Rol | combo obtenido desde `roles` |
| Estado | `ACTIVO` / `INACTIVO` |

No se precarga ni se muestra el `password_hash`.

---

## 6.4 Inactivar / Reactivar

No se requiere una pantalla independiente.

**Decisión UX del grupo:** la operación podrá ejecutarse desde el listado mediante una acción con confirmación. El estado también puede modificarse desde la pantalla Editar.

---

# 7. Flujos funcionales

## CU-01 — Listar y buscar

```text
Entrar al módulo
      ↓
Consultar usuarios + rol
      ↓
Mostrar listado
      ↓
¿Hay texto/filtro?
 ├─ No → mostrar todos
 └─ Sí
      ↓
Aplicar búsqueda + estado
      ↓
Mostrar coincidencias
      ↓
¿Cero coincidencias?
 └─ Mostrar mensaje sin resultados
```

## CU-02 — Registrar

```text
Abrir Registrar
      ↓
Cargar roles permitidos
      ↓
Completar formulario
      ↓
Validar obligatorios / longitudes / email
      ↓
Validar username y email duplicados
      ↓
Validar rol
      ↓
Generar hash
      ↓
Insertar usuario
      ↓
Mostrar resultado
      ↓
Volver al listado
```

## CU-03 — Editar

```text
Seleccionar Editar
      ↓
Buscar usuario por id_usuario
      ↓
Precargar datos
      ↓
Cargar roles permitidos
      ↓
Modificar datos
      ↓
Validar
      ↓
¿Nueva contraseña?
 ├─ No → conservar password_hash
 └─ Sí → generar nuevo hash
      ↓
Actualizar
      ↓
Mostrar resultado
```

## CU-04 — Inactivar / Reactivar

```text
Seleccionar acción de estado
      ↓
Identificar usuario
      ↓
Validar estado solicitado
      ↓
Actualizar estado
      ↓
Mostrar resultado
      ↓
Actualizar listado
```

---

# 8. Catálogo de mensajes funcionales

| ID | Situación | Mensaje propuesto |
|---|---|---|
| `MSG-01` | Registro correcto | `Usuario registrado correctamente.` |
| `MSG-02` | Edición correcta | `Usuario actualizado correctamente.` |
| `MSG-03` | Inactivación correcta | `Usuario inactivado correctamente.` |
| `MSG-04` | Reactivación correcta | `Usuario reactivado correctamente.` |
| `MSG-05` | Username repetido | `El username ya está registrado.` |
| `MSG-06` | Email repetido | `El email ya está registrado.` |
| `MSG-07` | Email inválido | `Ingrese un email válido.` |
| `MSG-08` | Campos requeridos | `Complete los campos obligatorios.` |
| `MSG-09` | Rol inválido | `Seleccione un rol válido.` |
| `MSG-10` | Estado inválido | `Seleccione un estado válido.` |
| `MSG-11` | Usuario inexistente | `No se encontró el usuario solicitado.` |
| `MSG-12` | Búsqueda sin resultados | `No se encontraron usuarios con los criterios indicados.` |
| `MSG-13` | Error no previsto | `No se pudo completar la operación. Intente nuevamente.` |
| `MSG-14` | Error al cargar roles | `No se pudo cargar la lista de roles.` |
| `MSG-15` | Contraseña faltante al crear | `La contraseña es obligatoria al registrar un usuario.` |

Los mensajes técnicos de MySQL no se mostrarán directamente al usuario final.

---

# 9. Definición de terminado funcional

Una historia se considerará lista para integración cuando:

- cumple todos sus criterios de aceptación;
- respeta las reglas de negocio relacionadas;
- no usa campos inexistentes;
- supera sus casos de prueba en `05_pruebas.md`;
- emplea consultas preparadas/parametrizadas;
- maneja errores previsibles con mensajes comprensibles;
- no amplía el alcance con login, permisos o CRUD de roles;
- puede ser explicada por el responsable de la historia.

---

# 10. Relación con otros documentos

- `03_modelo_datos.md`: estructura SQL real.
- `03_reglas_negocio.md`: restricciones y decisiones obligatorias.
- `05_pruebas.md`: casos de prueba y trazabilidad.
- `06_decisiones.md`: decisiones de alcance y diseño.
- `Manual_Maestro_Proyecto_Grupo1.md`: contexto y método general del proyecto.
