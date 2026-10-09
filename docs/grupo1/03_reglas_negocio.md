# 03 — Reglas de negocio — Grupo 1

> **Módulo:** CRUD de usuarios  
> **Tabla principal:** `usuarios`  
> **Tabla auxiliar:** `roles` como catálogo para seleccionar un rol mediante combo/lista desplegable  
> **Fuera de alcance confirmado:** CRUD de roles, login y sistema de permisos, salvo nueva indicación del docente.  
> **Estado:** BASELINE V1 — reglas cerradas para comenzar implementación.

## 1. Cómo leer este documento

Cada regla indica su origen para no confundir requisitos oficiales con decisiones de implementación:

- **DOCENTE:** indicación directa o documento oficial de la actividad.
- **SQL:** obligación derivada de `database.sql`.
- **DOCENTE + SQL:** ambos coinciden.
- **DECISIÓN DEL GRUPO:** comportamiento que el profesor y el SQL no fijan, adoptado para completar el CRUD sin ampliar el alcance.
- **BUENA PRÁCTICA:** criterio técnico para implementar de forma segura, sin modificar el modelo ni el alcance.

## 2. Reglas confirmadas

### RN-01 — El `username` es obligatorio y único

**Fuente:** DOCENTE + SQL.

- Debe existir al registrar un usuario.
- Longitud máxima estructural: 50 caracteres.
- No puede repetirse.
- Al editar, la validación de duplicado debe comparar contra **otros usuarios**, no contra el propio registro.

### RN-02 — El email es opcional y único si se proporciona

**Fuente:** SQL + DOCENTE para validación de formato.

`usuarios.email` es `VARCHAR(120) UNIQUE` y no tiene `NOT NULL`. El documento oficial pide validación de formato en los formularios, incluyendo correos cuando correspondan.

Consecuencias:

- el formulario puede aceptar email vacío;
- un email vacío debe tratarse como ausencia de valor y persistirse como `NULL`, no como un texto inventado;
- si se proporciona email, debe tener formato válido;
- si se proporciona email, no puede pertenecer a otro usuario;
- al editar, el usuario actual debe excluirse de la comprobación de duplicado.

### RN-03 — Todo usuario debe tener un rol existente

**Fuente:** DOCENTE + SQL.

- `usuarios.id_rol` es obligatorio (`NOT NULL`).
- Debe coincidir con un registro existente de `roles` por la FK `fk_usuario_rol`.
- El formulario de registro debe mostrar un combo/lista desplegable obtenido desde la tabla `roles`.
- Se asigna **un** rol por usuario.

### RN-04 — No se implementará CRUD de roles

**Fuente:** DOCENTE, confirmado directamente.

`roles` funciona como catálogo de apoyo para el CRUD de usuarios. El Grupo 1 no ofrecerá operaciones web para crear, editar, eliminar o inactivar roles.

### RN-05 — En el combo se mostrarán roles activos

**Fuente:** DECISIÓN DEL GRUPO apoyada por el campo `roles.estado`.

Para crear o cambiar el rol de un usuario, el selector debe presentar roles cuyo estado sea `ACTIVO`.

Además, el backend debe volver a validar que el `id_rol` recibido exista y esté permitido; no se confiará únicamente en el valor enviado por el `<select>`.

### RN-06 — Nombres y apellidos son obligatorios

**Fuente:** SQL.

- `nombres`: máximo 100 caracteres.
- `apellidos`: máximo 100 caracteres.
- Ambos son `NOT NULL`.

La aplicación debe rechazar valores vacíos después de quitar espacios exteriores.

### RN-07 — La contraseña debe almacenarse como hash

**Fuente:** DOCENTE + BUENA PRÁCTICA.

- Nunca se guardará la contraseña en texto plano.
- Al crear un usuario, se recibirá una contraseña y PHP generará el valor que se guardará en `password_hash`.
- No se implementarán algoritmos caseros de hash.

El docente exige explícitamente hash; OWASP recomienda almacenar passwords mediante funciones diseñadas para esta finalidad, no en texto plano.

### RN-08 — Comportamiento de contraseña al editar

**Fuente:** DECISIÓN DEL GRUPO.

Para mantener el CRUD simple:

- editar nombres, apellidos, email, username, rol o estado **no obliga** a cambiar la contraseña;
- si el campo de nueva contraseña se deja vacío, se conserva el `password_hash` existente;
- si se introduce una nueva contraseña, se genera un hash nuevo antes de actualizar.

No se mostrará nunca el hash actual en el formulario como si fuera una contraseña.

### RN-09 — No se inventará una política compleja de contraseña

**Fuente:** DECISIÓN DEL GRUPO.

El profesor exige hash, pero no fijó longitud mínima, mayúsculas, símbolos ni caducidad. Para no convertir una recomendación externa en un requisito académico inexistente:

- la contraseña será obligatoria al crear;
- no se impondrán por ahora reglas adicionales de complejidad no solicitadas;
- si el docente establece una política posteriormente, se incorporará y se documentará.

### RN-10 — Estado permitido del usuario

**Fuente:** SQL.

Solo existen:

- `ACTIVO`
- `INACTIVO`

El estado inicial es `ACTIVO` por default.

### RN-11 — Baja lógica en lugar de borrado físico

**Fuente:** DOCENTE.

La operación funcional de “eliminar” un usuario se implementará cambiando:

```text
estado → INACTIVO
```

El CRUD no utilizará `DELETE FROM usuarios` como operación normal de baja, aunque el usuario MySQL tenga técnicamente permiso `DELETE`.

### RN-12 — Se permitirá reactivar un usuario

**Fuente:** DECISIÓN DEL GRUPO, coherente con la historia del docente que permite cambiar el estado y con el `ENUM` del SQL.

Un usuario `INACTIVO` podrá volver a `ACTIVO` desde la edición/cambio de estado.

No se crea una tabla adicional ni un estado nuevo.

### RN-13 — El `username` podrá editarse

**Fuente:** DECISIÓN DEL GRUPO.

Se considera parte de los datos del usuario porque el docente exige editar usuarios y el SQL no lo hace inmutable.

Al modificarlo:

- sigue siendo obligatorio;
- sigue limitado a 50 caracteres;
- debe mantenerse único;
- la comprobación debe excluir el `id_usuario` que se está editando.

### RN-14 — No se implementará protección de “autoinactivación”

**Fuente:** ALCANCE CONFIRMADO.

El docente confirmó que el trabajo es solamente el CRUD de usuarios y no incluye login/autenticación. Por lo tanto, el sistema no conoce un “usuario actualmente autenticado” al cual aplicar una regla de autoinactivación.

No se añadirá esta lógica mientras login esté fuera del alcance.

### RN-15 — No se implementará una regla de “último administrador”

**Fuente:** DECISIÓN DE ALCANCE.

El proyecto no implementará autenticación ni control real de permisos por rol. Bloquear la inactivación del último `ADMINISTRADOR` añadiría una regla de negocio no indicada por el docente y que no es necesaria para cumplir el CRUD solicitado.

Si el alcance cambia y se añade autenticación, esta regla deberá revisarse.

### RN-16 — La fecha de creación la genera MySQL

**Fuente:** SQL.

`fecha_creacion` usa `DEFAULT CURRENT_TIMESTAMP`.

- No debe pedirse en el formulario.
- No debe calcularse manualmente para un alta normal.

### RN-17 — Búsqueda y filtros

**Fuente:** DOCENTE + DECISIÓN DE UX.

El docente pide buscar usuarios por nombre, username o estado.

Propuesta concreta:

- un campo de texto busca por `nombres`, `apellidos` o `username`;
- un selector de estado permite `TODOS`, `ACTIVO` o `INACTIVO`;
- ambos criterios pueden combinarse;
- por defecto se listarán todos los usuarios.

Esta solución cumple el requisito sin añadir filtros no solicitados.

### RN-18 — El rol mostrado al usuario será el nombre, pero se guardará `id_rol`

**Fuente:** DOCENTE + SQL.

La interfaz muestra nombres comprensibles como:

- `ADMINISTRADOR`
- `SUPERVISOR`
- `PERSONAL`

pero la tabla `usuarios` almacena el identificador correspondiente en `id_rol`.

### RN-19 — Validación en servidor

**Fuente:** BUENA PRÁCTICA.

La validación HTML/JavaScript mejora la experiencia, pero el backend PHP debe validar nuevamente los datos antes de insertar o actualizar.

Como mínimo:

- campos obligatorios;
- longitudes compatibles con el SQL;
- formato de email cuando exista;
- `id_rol` válido;
- estado permitido;
- duplicados de `username` y `email`.

OWASP recomienda realizar la validación del lado del servidor porque las validaciones del navegador pueden evitarse.

### RN-20 — Consultas preparadas/parametrizadas

**Fuente:** DOCENTE + BUENA PRÁCTICA.

Todas las operaciones que reciban datos del usuario deben usar PDO o mysqli con consultas preparadas; no se concatenarán entradas directamente dentro del SQL.

Además de ser requisito del docente, OWASP identifica las consultas parametrizadas como defensa principal contra SQL Injection.

### RN-21 — Mensajes de error comprensibles

**Fuente:** DOCENTE.

La interfaz debe transformar errores previsibles en mensajes claros, por ejemplo:

- “El username ya está registrado”.
- “El email ya está registrado”.
- “Seleccione un rol válido”.
- “Complete los campos obligatorios”.

No se debe depender únicamente del mensaje técnico devuelto por MySQL.

## 3. Reglas que NO aplican al alcance actual

No se diseñarán en esta etapa:

- inicio de sesión;
- cierre de sesión;
- sesiones PHP de autenticación;
- control de acceso por rol;
- matriz de permisos `ADMINISTRADOR` / `SUPERVISOR` / `PERSONAL`;
- CRUD de `roles`;
- relación `usuarios`–`personal`;
- recuperación de contraseña;
- bloqueo por intentos fallidos;
- auditoría de login.

Todas ellas requerirían ampliar el alcance o el modelo actual.

## 4. Estado de los huecos anteriores

| Hueco | Resolución |
|---|---|
| Reactivación | **Resuelto:** se permitirá `INACTIVO → ACTIVO` |
| Username editable | **Resuelto:** sí, manteniendo `UNIQUE` |
| Cambio de contraseña | **Resuelto:** opcional durante edición; vacío = conservar hash |
| Autoinactivación | **No aplica:** no existe login en el alcance |
| Último administrador | **No se implementa:** regla no exigida y permisos/login fuera de alcance |
| CRUD de roles | **Resuelto por docente:** no |
| Permisos por rol | **Fuera del alcance confirmado** |
| Listado inicial | **Resuelto:** todos |
| Búsqueda | **Resuelto:** texto nombre/username + filtro de estado |
| Duplicados al editar | **Resuelto:** comprobar otros usuarios excluyendo el actual |
| Estado inicial | **Resuelto por SQL:** `ACTIVO` |
| Login | **Resuelto por docente:** no; solamente CRUD de usuarios |
| Rol inexistente | **Resuelto por FK + validación de backend** |
| Email obligatorio | **Resuelto por SQL:** opcional |
| Rol inactivo asignable | **Resuelto por decisión:** no se mostrará/aceptará para nueva asignación |
| Vínculo usuario-personal | **Resuelto estructuralmente:** no existe; no se inventará |

## 5. Cierre de reglas para implementación

A partir de esta baseline:

- no quedan reglas funcionales críticas pendientes para comenzar el CRUD;
- las historias y criterios se documentan en `01_requisitos.md`;
- las pruebas y trazabilidad se documentan en `05_pruebas.md`;
- cualquier cambio posterior del docente debe registrarse primero en `06_decisiones.md`;
- una decisión nueva que contradiga otra anterior no elimina el historial: la anterior debe marcarse como reemplazada.

### Actor `administrador`

El término `administrador` se conserva porque así aparece en las historias del documento oficial. Dentro del alcance actual es un actor funcional/conceptual; no se implementará comprobación de autenticación ni permisos.

### Beneficio de la baja lógica

La baja lógica se mantiene por requisito del docente. No se implementará una supuesta relación de `usuarios` con incidentes o actividades porque esa relación no existe en `database.sql`.

---

## 6. Referencias técnicas de buenas prácticas

Estas fuentes se usan como apoyo de implementación, no para cambiar el alcance fijado por el docente:

- OWASP Cheat Sheet Series — Password Storage Cheat Sheet.
- OWASP Cheat Sheet Series — Input Validation Cheat Sheet.
- OWASP Cheat Sheet Series — SQL Injection Prevention Cheat Sheet.
