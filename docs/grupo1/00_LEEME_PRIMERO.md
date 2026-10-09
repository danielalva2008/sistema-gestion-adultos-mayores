# Grupo 1 — Documentación de referencia

## Proyecto

Sistema de Gestión de Residencia para Adultos Mayores.

Módulo del Grupo 1: CRUD de usuarios.

Esta carpeta contiene la documentación vigente utilizada por el
Grupo 1 para definir alcance, reglas, modelo de datos, pruebas y
decisiones técnicas.

---

## Para los integrantes

Cada integrante debe trabajar principalmente con el paquete operativo
individual que le entregó el coordinador.

No es necesario leer todos estos documentos antes de comenzar.

Si durante el desarrollo aparece una duda que el paquete individual
no resuelve, esta carpeta funciona como fuente de consulta.

Si la documentación tampoco resuelve la duda, consultar al coordinador
antes de inventar una solución.

---

## Documentos

### `01_requisitos.md`

Define:

- alcance funcional;
- historias de usuario;
- criterios de aceptación;
- pantallas;
- mensajes;
- funcionalidades fuera de alcance.

### `03_modelo_datos.md`

Define:

- tablas reales;
- columnas;
- tipos;
- PK;
- FK;
- UNIQUE;
- ENUM;
- relación entre `usuarios` y `roles`;
- elementos que no existen en la base.

La estructura real debe respetar `database.sql`.

### `03_reglas_negocio.md`

Contiene las reglas RN-01 a RN-21 que deben respetar las
implementaciones.

### `05_pruebas.md`

Contiene:

- CP-01 a CP-38;
- PT-01 a PT-05;
- criterios para considerar terminada una historia.

### `06_decisiones.md`

Registra decisiones tomadas por el Grupo 1 y aclaraciones del docente.

Si aparece una contradicción entre una decisión antigua y una nueva,
se utiliza la decisión vigente más reciente.

### `07_onboarding_y_reparto_equipo.md`

Describe:

- reparto de responsabilidades;
- revisión cruzada;
- organización de los seis integrantes.

### `08_preparacion_tecnica.md`

Registra:

- repositorio;
- rama real;
- entorno;
- PDO;
- conexión;
- estructura base preparada.

### `Manual_Maestro_Proyecto_Grupo1.md`

Contiene el contexto general y la visión completa del trabajo del
Grupo 1.

---

## Reglas rápidas

El Grupo 1 implementa únicamente el CRUD de `usuarios`.

`roles` funciona como catálogo.

No implementar:

- CRUD de roles;
- login;
- permisos;
- sesiones;
- tablas nuevas;
- columnas nuevas;
- relaciones nuevas.

Usar:

- PDO;
- consultas preparadas;
- `residencia_app`;
- baja lógica;
- validación en servidor.

No modificar `database.sql` por iniciativa propia.

---

## Rama de trabajo

La rama compartida vigente del Grupo 1 es:

`dev/grupo1`

Los integrantes desarrollan en sus ramas individuales y posteriormente
su trabajo se revisa antes de integrarse a `dev/grupo1`.

No trabajar directamente en `main`.

---

## Si utilizas IA

Primero utiliza el paquete operativo individual asignado a tu tarea.

Si necesitas información adicional, puedes consultar los documentos
de esta carpeta.

La IA no tiene autorización para ampliar el alcance ni modificar el
modelo de datos.

Si falta una decisión, consulta al coordinador.
