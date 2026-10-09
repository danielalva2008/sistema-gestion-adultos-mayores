# 06 — Registro de decisiones — Grupo 1

> Este archivo registra decisiones de alcance, interpretación y diseño. Las decisiones impuestas directamente por `database.sql` se documentan también en `03_modelo_datos.md`; aquí se conserva principalmente **por qué actuaremos de una determinada manera**.

## Estados

- **VIGENTE:** se utiliza actualmente.
- **PENDIENTE:** todavía requiere confirmación.
- **REEMPLAZADA:** dejó de aplicarse por una decisión posterior.

## Registro

| ID | Decisión | Tipo / fuente | Estado |
|---|---|---|---|
| `DEC-01` | `database.sql` es la fuente técnica principal para nombres, columnas, tipos, PK, FK, restricciones y relaciones. | SQL / metodología del proyecto | VIGENTE |
| `DEC-02` | No se agregarán columnas, tablas o relaciones al modelo sin indicación del docente. | Alcance | VIGENTE |
| `DEC-03` | El Grupo 1 implementará **solo el CRUD de usuarios**. | Docente — confirmación directa | VIGENTE |
| `DEC-04` | `roles` se usará como catálogo para asignar un rol a cada usuario mediante combo/lista desplegable. | Docente — confirmación directa | VIGENTE |
| `DEC-05` | No se implementará CRUD independiente de `roles`. | Docente — confirmación directa | VIGENTE |
| `DEC-06` | Los nombres de los roles no se duplicarán como una lista fija independiente en PHP; el combo se alimentará desde la tabla `roles`. | Consecuencia técnica de DEC-04 | VIGENTE |
| `DEC-07` | Para nuevas asignaciones/cambios se mostrarán roles `ACTIVO`. | Decisión del grupo | VIGENTE |
| `DEC-08` | No se implementarán login, sesiones ni autorización por rol porque el docente confirmó que el alcance es solamente el CRUD de usuarios. | Docente + alcance | VIGENTE |
| `DEC-09` | `email` será opcional en el formulario y único cuando se proporcione, siguiendo el SQL. | SQL | VIGENTE |
| `DEC-10` | Los usuarios nuevos usarán el estado `ACTIVO` por default. | SQL | VIGENTE |
| `DEC-11` | La baja será lógica: cambiar `estado` a `INACTIVO`; no se usará borrado físico como operación normal del CRUD. | Docente | VIGENTE |
| `DEC-12` | Se permitirá reactivar un usuario cambiando `INACTIVO` a `ACTIVO`. | Decisión del grupo | VIGENTE |
| `DEC-13` | Se permitirá editar `username`, manteniendo obligatoriedad, longitud y unicidad. | Decisión del grupo | VIGENTE |
| `DEC-14` | En edición, una contraseña vacía significa “conservar la actual”; si se introduce una nueva, se genera un nuevo hash. | Decisión del grupo | VIGENTE |
| `DEC-15` | No se añadirá una política propia de complejidad de contraseña mientras el docente no la exija; sí se exigirá almacenar hash. | Control de alcance | VIGENTE |
| `DEC-16` | No se implementará regla de autoinactivación porque no existe usuario autenticado en el alcance actual. | Consecuencia de DEC-08 | VIGENTE |
| `DEC-17` | No se implementará bloqueo del “último administrador” mientras autenticación/permisos estén fuera del alcance. | Control de alcance | VIGENTE |
| `DEC-18` | El listado inicial mostrará todos los usuarios; habrá búsqueda por nombres/apellidos/username y filtro de estado. | Decisión UX | VIGENTE |
| `DEC-19` | Todas las operaciones del CRUD utilizarán consultas preparadas/parametrizadas con PDO o mysqli. | Docente + buena práctica | VIGENTE |
| `DEC-20` | Las validaciones importantes se repetirán en el servidor, incluso si también existen controles HTML/JavaScript. | Buena práctica | VIGENTE |
| `DEC-21` | Las inconsistencias detectadas en `database.sql` no se corregirán silenciosamente. | Control de cambios | VIGENTE |
| `DEC-22` | Las cuatro historias oficiales se conservan, pero los beneficios que impliquen login/permisos o relaciones inexistentes se redactarán de acuerdo con el alcance real. | Alineación docente + SQL | VIGENTE |
| `DEC-23` | `administrador` se tratará como actor conceptual de las historias; no se comprobará autenticación porque el login está fuera del alcance. | Consecuencia de DEC-08 | VIGENTE |
| `DEC-24` | Un email vacío se tratará como ausencia de valor (`NULL`) para respetar que `usuarios.email` es opcional. | Consecuencia técnica del SQL | VIGENTE |
| `DEC-25` | Inactivar/reactivar podrá ejecutarse desde el listado mediante una acción con confirmación; el estado también será editable en la pantalla de edición. | Decisión UX | VIGENTE |
| `DEC-26` | La baseline de Requisitos y Lógica queda cerrada antes de iniciar PHP; los cambios posteriores deben registrarse primero como decisión. | Metodología del grupo | VIGENTE |
| `DEC-27` | La rama operativa vigente del Grupo 1 será `dev/grupo1`, creada/señalada por el docente y existente en el repositorio real. La mención previa a `feature/grupo-1-usuarios` queda como referencia del documento original, no como rama de trabajo actual. | Docente + repositorio real | VIGENTE |
| `DEC-28` | El Grupo 1 no modificará por ahora `Admin/Config/Conexion.php` ni `global.php`; usará `Admin/Config/ConexionPDO.php` para su conexión con PDO y `residencia_app`, reduciendo el riesgo de afectar código compartido existente. | Decisión técnica del grupo | VIGENTE |
| `DEC-29` | La estructura base del módulo seguirá la convención `Admin/modelos/Usuario.php`, `Admin/ajax/usuario.php` y `Admin/vistas/usuario.php`, compartiendo `ConexionPDO.php`. Las operaciones se añadirán historia por historia, no como CRUD completo de una sola vez. | Repositorio + decisión técnica | VIGENTE |
| `DEC-30` | En el entorno CachyOS del coordinador se utilizará XAMPP 8.2.12 con PHP 8.2.12 y MariaDB 10.4.32. Esta compatibilidad local fue verificada para `roles`, `usuarios`, `residencia_app` y PDO, sin modificar el modelo oficial. | Entorno técnico verificado | VIGENTE |
| `DEC-31` | El repositorio del coordinador se ejecutará directamente desde `/opt/lampp/htdocs/sistema-residencia` para que Apache pueda servirlo sin duplicar el repositorio ni abrir permisos del directorio personal. Esta ruta es una decisión local de entorno, no una obligación para todos los integrantes. | Entorno local | VIGENTE |

## Detalle de decisiones importantes

### DEC-03 / DEC-04 / DEC-05 — Alcance definitivo del docente

El docente aclaró directamente que:

- solamente se implementa el CRUD de usuarios;
- un usuario tiene un rol;
- los roles deben estar en la base de datos;
- en el registro de usuario debe aparecer la lista de roles en un combo;
- se asigna un rol al usuario;
- no se implementa CRUD de roles.

Esta aclaración reemplaza cualquier interpretación anterior que sugiriera construir administración completa de `roles`.

### DEC-08 — Sin login ni permisos dentro del entregable actual

Ante la consulta de alcance, el docente volvió a indicar que es **solamente el CRUD de usuarios**. Por tanto, no se diseñará un subsistema adicional de autenticación/autorización salvo nueva indicación.

Los valores `ADMINISTRADOR`, `SUPERVISOR` y `PERSONAL` siguen siendo datos válidos del usuario, pero el Grupo 1 no debe transformar esa asignación en un sistema de control de acceso que no fue solicitado.

### DEC-12 — Reactivación

El SQL permite `ACTIVO` e `INACTIVO`, y la historia del docente permite cambiar el estado. Para que la gestión de estado sea simétrica y útil, se permitirá reactivar cuentas sin crear registros nuevos.

### DEC-14 — Contraseña durante Update

No se puede precargar una contraseña original porque la aplicación debe almacenar solamente su hash. Por ello, al editar:

```text
nueva contraseña vacía  → conservar password_hash existente
nueva contraseña escrita → generar hash nuevo y actualizar
```

Nunca se mostrará el `password_hash` al usuario como contraseña editable.

### DEC-18 — Búsqueda sencilla y suficiente

Para cumplir la historia del docente sin sobrecargar el módulo:

```text
[ Buscar por nombre o username ........ ] [ Estado: TODOS ▼ ]
```

El campo de texto buscará por nombres, apellidos y username; el estado será un filtro separado y combinable.


### DEC-22 / DEC-23 — Historias y actor sin ampliar el alcance

El documento oficial utiliza historias con el actor `administrador` y menciona beneficios relacionados con ingreso al sistema, permisos y trazabilidad.

La aclaración posterior del docente limita el entregable al CRUD de `usuarios`, sin login ni permisos. Además, `database.sql` no crea una relación entre `usuarios` y `personal`, `incidentes` o `actividades`.

Por tanto:

- se conservan las cuatro acciones funcionales de las historias;
- `administrador` queda como actor conceptual;
- los beneficios se redactan sin prometer funcionalidades que el módulo no implementará.

### DEC-24 — Email vacío

Como `usuarios.email` admite `NULL`, un formulario sin email representará ausencia de correo.

No se utilizará un correo ficticio ni se asumirá que una cadena vacía es un correo real.

### DEC-25 — Ubicación de las acciones de estado

La interfaz puede ofrecer:

- `Inactivar` o `Reactivar` desde el listado;
- cambio de `estado` dentro de Editar.

Ambos caminos modifican el mismo campo `usuarios.estado` y deben cumplir las mismas validaciones.

### DEC-26 — Congelamiento de baseline

Se considera cerrada la fase de requisitos cuando existen:

- alcance;
- historias finales;
- reglas de negocio;
- criterios de aceptación;
- pantallas;
- flujos;
- catálogo de mensajes;
- casos de prueba;
- trazabilidad.

Después de ese punto puede iniciarse la preparación técnica e implementación. Si el docente cambia el alcance, primero se actualiza este registro.


### DEC-27 — Rama operativa real

El documento original de formulación enumera `feature/grupo-1-usuarios`, pero el repositorio real contiene ramas `dev/grupo1`, `dev/grupo2`, etc., y el docente indicó al Grupo 1 trabajar en `dev/grupo1`. Por prioridad de la indicación directa del docente, esa es la rama vigente.

No se trabaja directamente sobre `main`. Antes de repartir historias se definirá el flujo Git interno del equipo para evitar conflictos entre seis integrantes.

### DEC-28 — Conexión PDO aislada de la configuración existente

El repositorio ya contenía `Admin/Config/Conexion.php` y `global.php` con una conexión basada en `mysqli` y `root`. Para no alterar infraestructura potencialmente compartida, el Grupo 1 añadió `Admin/Config/ConexionPDO.php`.

La conexión fue probada con:

```text
PHP → PDO → residencia_app → sistema_residencia
```

y también mediante:

```text
Navegador → Apache → PHP → PDO → residencia_app → sistema_residencia
```

### DEC-29 — Estructura mínima del módulo

La base común acordada es:

```text
Admin/
├── Config/
│   └── ConexionPDO.php
├── modelos/
│   └── Usuario.php
├── ajax/
│   └── usuario.php
└── vistas/
    └── usuario.php
```

Esta estructura no significa que una persona sea dueña de un archivo completo. El trabajo seguirá repartiéndose por historias de usuario.

### DEC-30 / DEC-31 — Entorno local del coordinador

La preparación técnica se realizó en CachyOS con XAMPP 8.2.12, PHP 8.2.12 y MariaDB 10.4.32. La diferencia MySQL/MariaDB se registra como característica del entorno local; no autoriza cambios en `database.sql`.

El repositorio se ubicó en `/opt/lampp/htdocs/sistema-residencia` porque Apache no podía atravesar el directorio personal del usuario mediante enlace simbólico. No se aplicaron permisos inseguros como `chmod 777`.


## Incidencias conocidas de la base oficial

Estas incidencias no son decisiones del Grupo 1 y no deben corregirse por iniciativa propia:

| ID | Incidencia |
|---|---|
| `INC-BD-01` | El documento de formulación dice 10 tablas, pero `database.sql` crea 9 |
| `INC-BD-02` | El documento indica 30 participaciones, pero el SQL contiene 31 tuplas |
| `INC-BD-03` | Una tupla de `incidentes` tiene un valor faltante; el `INSERT` es inválido tal como está |
| `INC-BD-04` | El hash del usuario `admin` no verifica con la clave comentada `Admin123*` |

### Tratamiento

- No inventar el valor faltante de `INC-BD-03`.
- No sustituir silenciosamente el hash de `INC-BD-04`.
- Si alguna incidencia bloquea una tarea del Grupo 1, consultar al docente antes de modificar el SQL oficial.

## Decisiones futuras

La fase de **Requisitos y Lógica de Negocio queda cerrada en baseline V1**. No quedan huecos críticos para comenzar preparación técnica e implementación del CRUD de usuarios.

Si el docente cambia el alcance (por ejemplo, añade login, permisos o CRUD de roles), se añadirá una nueva decisión y se marcarán como `REEMPLAZADA` las decisiones afectadas; no se borrará el historial.
