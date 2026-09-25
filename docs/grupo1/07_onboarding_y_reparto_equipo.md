# 07 — Onboarding y reparto del equipo — Grupo 1

> **Objetivo:** que seis integrantes principiantes comiencen desde la misma base y puedan trabajar en paralelo sin improvisar el alcance.

---

# 1. Cuándo se reparte el trabajo

El reparto se activa **después** de que todo el equipo pueda:

- abrir XAMPP o su entorno local equivalente acordado;
- levantar Apache y el servidor MySQL/MariaDB;
- abrir el repositorio;
- ubicarse en la rama vigente del Grupo 1: `dev/grupo1`;
- actualizar su copia sin trabajar directamente en `main`;
- importar/usar `database.sql`;
- identificar `usuarios` y `roles`;
- comprobar, al menos una vez, que la base responde con el usuario técnico `residencia_app`;
- comprender qué significa, a nivel básico:

```text
Navegador → PHP → PDO → MySQL
```

No es necesario dominar PHP antes de empezar.

Sí es necesario saber **qué funcionalidad se está implementando y qué no se debe inventar**.

---

# 2. Reunión de onboarding recomendada

Duración sugerida: 45–60 minutos.

## Bloque A — 10 min: qué estamos construyendo

Explicar únicamente:

- Grupo 1 implementa CRUD de `usuarios`;
- `roles` es catálogo;
- no hay CRUD de roles;
- no hay login;
- no hay permisos;
- la baja es lógica;
- `database.sql` manda sobre nombres y campos.

## Bloque B — 15 min: base de datos

Abrir `usuarios` y `roles`.

Todos deben identificar:

```text
usuarios
- id_usuario
- id_rol
- username
- password_hash
- nombres
- apellidos
- email
- estado
- fecha_creacion

roles
- id_rol
- nombre
- descripcion
- estado
```

## Bloque C — 10 min: flujo web

Explicar:

```text
Formulario HTML
      ↓
PHP recibe datos
      ↓
valida
      ↓
PDO ejecuta consulta
      ↓
MySQL guarda/consulta
      ↓
PHP muestra resultado
```

## Bloque D — 10 min: Git

Cada integrante debe saber al menos:

- `git status`;
- `git branch --show-current`;
- cambiar a la rama de trabajo que se le indique;
- actualizar su trabajo antes de empezar una sesión;
- `git add`;
- `git commit`;
- `git push`;
- que no debe trabajar directamente en `main`;
- que `dev/grupo1` es la rama compartida vigente del Grupo 1, pero el flujo de ramas individuales se define antes de iniciar implementación paralela.

## Bloque E — 10 min: cómo pedir ayuda a IA

Nunca:

> “Hazme un CRUD.”

Sí:

> “Implemento HU-01. Estas son mis reglas, criterios y pruebas. No cambies database.sql. Explícame primero el flujo y luego indícame qué archivos tocar.”

---

## Estado técnico común ya preparado por coordinación

La rama `dev/grupo1` ya contiene una base técnica mínima comprobada:

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

La conexión compartida usa PDO y la cuenta técnica `residencia_app`. Ningún integrante debe crear una conexión paralela ni reemplazar `database.sql` por una versión propia.

Antes de entregar las fichas funcionales todavía debe quedar acordado el **flujo Git interno para seis integrantes** (ramas individuales, actualización e integración).


# 3. Reparto recomendado

## Persona 1 — Coordinación e integración

**Responsabilidad:**

- mantener contexto;
- estructura base del módulo;
- conexión PDO;
- integración;
- revisión general;
- resolver conflictos;
- comprobar que no se amplía el alcance.

**No debe convertirse en la persona que hace todo.**

---

## Persona 2 — HU-02 Listado y búsqueda

**Responsable de:**

- listado de usuarios;
- mostrar nombre del rol;
- búsqueda por nombres/apellidos/username;
- filtro de estado;
- mensaje sin resultados.

**Pruebas principales:** `CP-14` a `CP-22`.

---

## Persona 3 — HU-01 Registro

**Responsable de:**

- formulario de registro;
- carga de roles;
- campos obligatorios;
- username/email duplicados;
- contraseña → hash;
- alta en estado `ACTIVO`;
- mensajes.

**Pruebas principales:** `CP-01` a `CP-13`.

---

## Persona 4 — HU-03 Edición

**Responsable de:**

- precarga de datos;
- edición de nombres, apellidos, email y username;
- cambio de rol;
- nueva contraseña opcional;
- cambio de estado;
- validación de duplicados excluyendo el propio usuario.

**Pruebas principales:** `CP-23` a `CP-34`.

---

## Persona 5 — HU-04 Estado + apoyo de validaciones

**Responsable de:**

- inactivar;
- reactivar;
- confirmaciones;
- comprobar que la baja es lógica;
- apoyar revisión de validaciones del servidor.

**Pruebas principales:** `CP-35` a `CP-38` + apoyo `PT-02`.

---

## Persona 6 — Interfaz, pruebas y documentación

**Responsabilidad:**

- consistencia visual;
- mensajes;
- ayudar a ejecutar el plan de pruebas;
- registrar evidencias;
- README;
- capturas;
- revisar que las pantallas mantengan el mismo estilo.

**No debe limitarse a “hacer CSS”:** participa en pruebas funcionales.

---

# 4. Parejas de revisión

| Funcionalidad | Responsable | Revisor sugerido |
|---|---|---|
| HU-01 Registro | Persona 3 | Persona 6 |
| HU-02 Listado | Persona 2 | Persona 5 |
| HU-03 Edición | Persona 4 | Persona 3 |
| HU-04 Estado | Persona 5 | Persona 4 |
| Base/PDO | Persona 1 | Persona 2 |
| README/pruebas | Persona 6 | Persona 1 |

La revisión no significa reescribir el trabajo. Significa comprobarlo contra requisitos y pruebas.

---

# 5. Ficha que recibe cada integrante

Cada tarea debe entregarse así:

```text
HISTORIA:
HU-xx — nombre

OBJETIVO:
qué debe lograr

CAMPOS:
solo los existentes en database.sql

REGLAS:
RN aplicables

CRITERIOS:
CA aplicables

PRUEBAS:
CP aplicables

NO IMPLEMENTAR:
funcionalidades fuera de alcance

TERMINADO CUANDO:
criterios cumplidos + pruebas aprobadas
```

---

# 6. Fichas resumidas

## FICHA A — HU-01 Registro

**Objetivo:** registrar un usuario asignándole un rol existente.

**Campos:**

- nombres;
- apellidos;
- email;
- username;
- contraseña de entrada → `password_hash`;
- rol → `id_rol`.

**Reglas:** RN-01, RN-02, RN-03, RN-05, RN-06, RN-07, RN-09, RN-10, RN-16, RN-18, RN-19, RN-20, RN-21.

**Pruebas:** CP-01 a CP-13.

**No implementar:**

- login;
- permisos;
- CRUD de roles;
- campos adicionales.

---

## FICHA B — HU-02 Listado

**Objetivo:** ubicar usuarios rápidamente.

**Mostrar:**

- username;
- nombres;
- apellidos;
- email;
- rol;
- estado;
- fecha de creación;
- acciones.

**Reglas:** RN-17, RN-18, RN-19, RN-20, RN-21.

**Pruebas:** CP-14 a CP-22.

---

## FICHA C — HU-03 Edición

**Objetivo:** mantener los datos actualizados.

**Editar:**

- nombres;
- apellidos;
- email;
- username;
- nueva contraseña opcional;
- rol;
- estado.

**Reglas:** RN-01, RN-02, RN-03, RN-05, RN-06, RN-08, RN-10, RN-12, RN-13, RN-18, RN-19, RN-20, RN-21.

**Pruebas:** CP-23 a CP-34.

---

## FICHA D — HU-04 Estado

**Objetivo:** inactivar sin borrar y permitir reactivación.

**Reglas:** RN-10, RN-11, RN-12, RN-19, RN-20, RN-21.

**Pruebas:** CP-35 a CP-38.

---

# 7. Regla de integración

No integrar porque alguien diga:

> “Ya acabé.”

Preguntar:

```text
¿Cumple la historia?
¿Cumple sus reglas?
¿Pasan sus pruebas?
¿Usa solo columnas reales?
¿Usa prepared statements?
¿Maneja errores?
¿El responsable puede explicar lo que hizo?
```

Si una respuesta es “no”, aún no está terminada.

---

# 8. Orden recomendado a partir de aquí

```text
BASELINE DE REQUISITOS            ✅
          ↓
REPOSITORIO + RAMA REAL           ✅ dev/grupo1
          ↓
XAMPP / BD / residencia_app       ✅
          ↓
CONEXIÓN PDO                      ✅
          ↓
ARQUITECTURA MÍNIMA               ✅
          ↓
DEFINIR FLUJO GIT DEL EQUIPO      ← AHORA
          ↓
ONBOARDING DE LOS 6
          ↓
REPARTIR FICHAS
          ↓
IMPLEMENTAR EN PARALELO
          ↓
REVISIÓN CRUZADA
          ↓
INTEGRACIÓN
          ↓
PRUEBAS
          ↓
README + CAPTURAS
          ↓
PULL REQUEST
```

## 8.1 Momento exacto para entregar las fichas

Las fichas HU se entregan cuando se cumplan simultáneamente estas condiciones:

1. el flujo Git interno ya está definido;
2. cada integrante puede abrir el repositorio y ubicarse en su rama de trabajo;
3. cada integrante tiene su entorno local funcionando o sabe cómo usar la base preparada;
4. todos entienden que `ConexionPDO.php` y la estructura base son compartidas;
5. cada responsable recibe una historia con reglas, criterios y pruebas, no un archivo aislado.

Hasta entonces pueden delegarse tareas de onboarding, instalación, clonación, lectura de requisitos y pruebas, pero no implementación independiente del CRUD.

