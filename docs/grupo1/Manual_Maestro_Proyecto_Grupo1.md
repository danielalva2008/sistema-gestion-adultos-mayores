# Manual maestro del proyecto — Grupo 1: Usuarios y Roles

> **Proyecto:** Sistema de Gestión de Residencia para Adultos Mayores  
> **Curso:** Desarrollo de Plataformas  
> **Grupo:** 1 — Usuarios y Roles  
> **Tecnologías base:** PHP 8.x, MySQL 8.x, XAMPP, Git y GitHub  
> **Estado:** documento vivo. La fase de Requisitos y Lógica quedó cerrada en **Baseline V1**; el siguiente foco es preparación técnica e implementación.

## 1. Para qué existe este documento

Este archivo sirve como **fuente de verdad del Grupo 1**. Su objetivo es evitar que el conocimiento importante quede disperso en conversaciones largas de ChatGPT, mensajes del grupo, capturas de clase o recuerdos de cada integrante.

Regla de trabajo recomendada:

1. El docente y sus archivos son la fuente principal.
2. `database.sql` define la estructura técnica real de la base de datos.
3. El repositorio define la organización real del código.
4. Normas y protocolos externos sirven para completar huecos o justificar decisiones, pero no para reemplazar los requisitos del docente.
5. Las decisiones nuevas del grupo deben anotarse aquí.
6. El código generado por IA debe comprobarse contra este documento antes de aceptarse.

---

# PARTE I — COMPRENDER EL PROYECTO

## 2. Qué estamos construyendo

El proyecto completo es una aplicación web para gestionar una residencia de adultos mayores. La clase se divide en varios grupos y cada grupo implementa un módulo.

El Grupo 1 tiene asignado el módulo **Usuarios y Roles**. Su responsabilidad principal es administrar las cuentas de acceso del sistema y su relación con los roles definidos en la base de datos.

El módulo debe cubrir, como mínimo:

- registrar usuarios;
- listar usuarios;
- buscar usuarios;
- editar sus datos;
- cambiar rol o estado;
- inactivar usuarios mediante baja lógica;
- validar `username` único;
- validar `email` único;
- almacenar contraseñas con hash;
- respetar las restricciones definidas en `database.sql`.

## 3. Cómo funciona técnicamente una aplicación web de este tipo

```text
USUARIO
   ↓
NAVEGADOR
   ↓ HTTP
APACHE (XAMPP)
   ↓
PHP
   ↓ PDO / mysqli
MYSQL
   ↓
PHP procesa la respuesta
   ↓
HTML
   ↓
NAVEGADOR
```

### 3.1 Navegador
Muestra la interfaz y envía peticiones al servidor.

### 3.2 XAMPP
Proporciona el entorno local. Los componentes importantes son Apache, MySQL/MariaDB, phpMyAdmin y PHP.

**Estado técnico verificado (coordinador, CachyOS):** XAMPP 8.2.12, PHP 8.2.12 y MariaDB 10.4.32. El documento oficial sigue tomando MySQL 8.x como tecnología de referencia; esta diferencia de entorno no modifica `database.sql` ni el modelo. Para el Grupo 1 se verificó que `roles`, `usuarios`, el usuario técnico `residencia_app` y PDO funcionan correctamente en este entorno. Los detalles reproducibles quedan en `08_preparacion_tecnica.md`.

### 3.3 Apache
Recibe las peticiones del navegador y permite ejecutar archivos PHP.

### 3.4 PHP
Contiene la lógica del servidor: recibe formularios, valida datos, consulta la base, inserta, actualiza y decide qué mostrar.

### 3.5 MySQL
Almacena los datos y aplica restricciones.

### 3.6 PDO
Es una interfaz de PHP para conectarse a MySQL.

```text
PHP
 ↓
PDO
 ↓
MySQL
```

El proyecto exige consultas preparadas o parametrizadas.

## 4. Frontend y backend

**Frontend:** HTML, CSS, Bootstrap, formularios, tablas, botones y alertas.

**Backend:** PHP, validaciones, PDO, SQL, MySQL y manejo de errores.

Un formulario bonito que no guarda nada es solo interfaz. Un `INSERT` que funciona pero no tiene interfaz usable es solo backend. El CRUD necesita ambas partes conectadas.

---

# PARTE II — BASE DE DATOS Y CRUD

## 5. Qué significa CRUD

- **Create:** crear.
- **Read:** consultar.
- **Update:** actualizar.
- **Delete:** eliminar.

En Usuarios, Delete debe interpretarse principalmente como **baja lógica**.

```sql
UPDATE usuarios
SET estado = 'INACTIVO'
WHERE id_usuario = ?;
```

La idea es conservar historial y trazabilidad.

## 6. Elementos esenciales de SQL

- **PK:** identifica un registro de forma única.
- **FK:** relaciona una tabla con otra.
- **UNIQUE:** impide duplicados.
- **NOT NULL:** obliga a que un campo tenga valor.
- **ENUM:** limita un campo a valores definidos.
- **CHECK:** impone una condición.
- **AUTO_INCREMENT:** genera identificadores automáticamente.

## 7. Qué es `database.sql`

`database.sql` es uno de los artefactos técnicos más importantes. Debe analizarse antes de programar porque determina:

- nombres exactos de tablas;
- nombres exactos de columnas;
- tipos de datos;
- PK y FK;
- `UNIQUE`, `CHECK`, `ENUM`;
- `NULL` / `NOT NULL`;
- datos de prueba;
- usuario de aplicación;
- privilegios.

**La base de datos manda sobre el formulario. No debemos inventar columnas que no existan.**

---

# PARTE III — REQUISITOS Y LÓGICA DE NEGOCIO

## 8. Qué ya está definido para Grupo 1

### Funcionalidades
1. Registrar usuarios indicando su rol.
2. Listar y buscar por nombre, username o estado.
3. Editar datos.
4. Cambiar rol.
5. Cambiar estado.
6. Inactivar en lugar de eliminar físicamente.

### Reglas principales
- `username` único.
- `email` único.
- password almacenado con hash.
- baja lógica para conservar historial.

## 9. Huecos de lógica de negocio — RESUELTOS

La fase de requisitos fue cerrada en la baseline V1. Los huecos anteriores quedaron así:

| Tema | Resolución vigente |
|---|---|
| Reactivación | Sí: `INACTIVO → ACTIVO` |
| Edición de username | Sí, manteniendo obligatoriedad, longitud y unicidad |
| Cambio de contraseña | Opcional en edición; vacío = conservar hash |
| Autoinactivación | No aplica porque no existe login |
| Último administrador | No se implementa |
| Gestión de roles | Solo catálogo; no CRUD de roles |
| Permisos | Fuera del alcance |
| Listado inicial | Todos los usuarios |
| Búsqueda | Texto por nombres/apellidos/username + filtro de estado |
| Duplicados al editar | Se comparan otros usuarios, excluyendo el actual |
| Email | Opcional; único si se proporciona |
| Estado inicial | `ACTIVO` por default |
| Login | Fuera del alcance |
| Rol inexistente/inactivo | No se acepta para nuevas asignaciones/cambios |
| Relación usuario-personal | No existe en `database.sql`; no se inventa |

Los detalles normativos quedan en `03_reglas_negocio.md`; las decisiones quedan en `06_decisiones.md`.

## 10. Orden para resolver nuevos huecos

```text
¿Lo dice el documento?
        │
        ├─ Sí → cumplirlo.
        └─ No
             ↓
¿Lo define database.sql?
        │
        ├─ Sí → respetarlo.
        └─ No
             ↓
¿Una norma/protocolo lo justifica?
        │
        ├─ Sí → propuesta fundamentada.
        └─ No
             ↓
Decisión del grupo
        ↓
Validar con docente si afecta el alcance.
```

---

# PARTE IV — HISTORIAS, REGLAS Y PRUEBAS

## 11. Historias de usuario finales

La baseline funcional contiene cuatro historias:

1. `HU-01` — Registrar usuario indicando su rol.
2. `HU-02` — Listar y buscar usuarios por nombre, username o estado.
3. `HU-03` — Editar datos y cambiar rol o estado.
4. `HU-04` — Inactivar mediante baja lógica.

La redacción detallada y los criterios están en `01_requisitos.md`.

## 12. Reglas de negocio

Las reglas vigentes están identificadas como `RN-01` a `RN-21` en `03_reglas_negocio.md`.

Las fuentes se distinguen explícitamente entre:

- DOCENTE;
- SQL;
- DECISIÓN DEL GRUPO;
- BUENA PRÁCTICA.

## 13. Criterios de aceptación

Cada historia tiene criterios `CA-HU.n` que describen cuándo puede considerarse funcionalmente terminada.

Ejemplo conceptual:

```text
HU-01 Registro
   ↓
reglas aplicables
   ↓
criterios de aceptación
   ↓
casos de prueba
```

## 14. Casos de prueba y trazabilidad

El plan definitivo se encuentra en `05_pruebas.md` e incluye:

- `CP-01` a `CP-38`;
- pruebas técnicas `PT-01` a `PT-05`;
- matriz Historia → Regla → Criterio → Prueba;
- registro de ejecución;
- criterio de integración.

La regla operativa para el equipo será:

```text
No asignar "un archivo".
Asignar una historia con sus reglas, criterios y pruebas.
```

---

# PARTE V — DIAGRAMAS

## 15. Diagrama entidad-relación

Debe construirse a partir de `database.sql`.

```text
ROLES
  1
  │
  N
USUARIOS
```

## 16. Diagrama de arquitectura

```text
Navegador
   ↓
Apache
   ↓
PHP
   ↓
PDO
   ↓
MySQL
```

## 17. Diagrama de flujo de registro

```text
Inicio
  ↓
Mostrar formulario
  ↓
Ingresar datos
  ↓
¿Datos válidos?
  ├─ No → mostrar errores
  └─ Sí
       ↓
¿username/email ya existen?
  ├─ Sí → error
  └─ No
       ↓
Generar hash
       ↓
INSERT
       ↓
¿Correcto?
  ├─ No → error
  └─ Sí → mensaje de éxito
```

## 18. BPMN

BPMN puede ser útil, pero no aparece como entregable obligatorio en el documento revisado. Para Usuarios y Roles probablemente basta un diagrama de flujo, salvo que el docente exija BPMN.

---

# PARTE VI — PROTOCOLOS Y NORMAS

## 19. Para qué sirven

Sirven para comprender actores, responsabilidades, procesos, trazabilidad, información que debe conservarse, estados, permisos y validaciones.

No reemplazan el alcance del profesor.

## 20. Jerarquía de fuentes

```text
1. Documento del docente
2. database.sql
3. Repositorio oficial
4. Indicaciones de clase
5. Normas/protocolos
6. Buenas prácticas de ingeniería
7. Decisiones del grupo
```

## 21. Del protocolo al código

```text
NECESIDAD REAL
      ↓
REQUISITO
      ↓
REGLA DE NEGOCIO
      ↓
HISTORIA DE USUARIO
      ↓
CASO DE PRUEBA
      ↓
CÓDIGO
```

---

# PARTE VII — GIT Y GITHUB

## 22. Git

Controla versiones localmente.

## 23. GitHub

Aloja y coordina el repositorio remoto.

## 24. Conceptos esenciales

- **clone:** trae el repositorio a la PC.
- **branch:** línea independiente de trabajo.
- **commit:** punto registrado de cambios.
- **push:** envía commits al remoto.
- **pull:** trae cambios remotos.
- **Pull Request:** propone integrar una rama en otra.

## 25. Flujo mental

```text
Tu PC
  ↓
editar
  ↓
git status
  ↓
git add
  ↓
git commit
  ↓
git push
  ↓
GitHub
  ↓
Pull Request
  ↓
revisión
  ↓
merge
```

### 25.1 Rama operativa real del Grupo 1

El documento original de formulación menciona `feature/grupo-1-usuarios`, pero el docente creó y señaló para el trabajo del equipo la rama real `dev/grupo1`. En el repositorio también existen `dev/grupo2`, `dev/grupo3`, etc.

Por indicación directa del docente y por el estado real del repositorio, la rama operativa vigente del Grupo 1 es:

```text
dev/grupo1
```

Reglas actuales:

- no trabajar directamente en `main`;
- mantener `dev/grupo1` como rama compartida del Grupo 1;
- antes de iniciar implementación paralela, definir el flujo interno de ramas individuales o de integración para evitar que seis integrantes se pisen entre sí;
- cualquier cambio futuro del docente sobre la estrategia de ramas reemplaza esta decisión y debe registrarse.

## 26. Commits descriptivos

Buenos:

```text
feat: implementar listado de usuarios
feat: agregar formulario de registro
fix: validar email duplicado
docs: actualizar README
refactor: separar conexión PDO
```

Evitar:

```text
cambios
final
final2
prueba
ahora si
```

---

# PARTE VIII — ORGANIZACIÓN DEL EQUIPO

## 27. No delegar archivos; delegar funcionalidades

No: “tú haces `crear.php`”.

Sí: “tú implementas HU-01 Registro de usuario, respetando RN-01, RN-02 y RN-03 y pasando CP-01, CP-02 y CP-03”.

## 28. Posible división para seis integrantes

| Persona | Responsabilidad principal |
|---|---|
| 1 | Integración, XAMPP, conexión PDO y Git |
| 2 | Listado, búsqueda y filtros |
| 3 | Registro de usuarios |
| 4 | Edición y cambio de rol |
| 5 | Baja lógica y validaciones |
| 6 | Interfaz, pruebas y documentación |

Todos deben poder explicar el flujo general.

---

# PARTE IX — USAR IA CORRECTAMENTE

## 29. Vibecoding bien hecho

```text
HUMANO
  ↓ define
REQUISITO
  ↓
IA
  ↓ propone
IMPLEMENTACIÓN
  ↓
HUMANO
  ↓ valida
PRUEBAS
```

## 30. Qué debe hacer el humano

- entender el problema;
- decidir el alcance;
- distinguir requisitos de sugerencias;
- revisar que no se inventen columnas;
- verificar reglas;
- ejecutar pruebas;
- decidir si aceptar cambios;
- coordinar integración.

## 31. Qué puede hacer bien la IA

- análisis de requisitos;
- arquitectura;
- generación de código;
- revisión;
- testing;
- documentación.

## 32. Prompt pobre vs útil

Pobre:

> Hazme un CRUD PHP.

Mejor:

> Implementa HU-01 Registro de usuario. La tabla real es esta. Debe validar username y email únicos, usar `password_hash()`, PDO y consultas preparadas, no modificar `database.sql` y respetar la estructura existente. Antes de escribir código, indica qué archivos modificarás; después explica cómo probarlo.

## 33. Regla de oro

**“Funciona” no significa “está bien”.**

Puede funcionar y aun incumplir baja lógica, prepared statements, hash o el esquema real.

---

# PARTE X — CÓMO GESTIONAR LOS CHATS

## 34. Por qué no conviene un único chat infinito

Una conversación extremadamente larga puede mezclar etapas, acumular decisiones obsoletas y hacer menos claro qué información sigue vigente.

La solución es **externalizar el contexto importante en archivos vivos**.

## 35. Estrategia recomendada de conversaciones

- **Chat 00 — Dirección del proyecto:** alcance, decisiones y planificación.
- **Chat 01 — Base de datos:** `database.sql`, ER, PK/FK y SQL.
- **Chat 02 — Requisitos y lógica:** historias, reglas, protocolos y criterios.
- **Chat 03 — Git/GitHub:** ramas, commits, conflictos y PR.
- **Chat 04 — Implementación PHP:** PDO, CRUD, HTML/Bootstrap.
- **Chat 05 — Testing:** casos de prueba, errores y validación.
- **Chat 06 — Sustentación:** README, capturas y exposición.

## 36. Qué llevar de un chat a otro

No copiar toda la conversación. Mantener archivos como:

```text
/docs/00_contexto_maestro.md
/docs/01_requisitos.md
/docs/02_reglas_negocio.md
/docs/03_modelo_datos.md
/docs/04_decisiones.md
/docs/05_pruebas.md
```

Cada nuevo chat puede comenzar indicando qué archivos son la fuente vigente.

## 37. Registro de decisiones

| ID | Decisión | Motivo | Fuente |
|---|---|---|---|
| DEC-01 | Usar PDO | Unificar acceso a datos | Equipo |
| DEC-02 | Baja lógica | Requisito del docente | Documento |

Si una decisión cambia, se marca como reemplazada; no se borra silenciosamente.

---

# PARTE XI — CHAT, WORK, CODEX, AGENTES Y SKILLS

## 38. Chat

Úsalo para aprender, preguntar, discutir opciones, comprender errores y tomar decisiones.

## 39. Work

Es apropiado para tareas largas de varios pasos con un entregable final claro, por ejemplo:

- auditar todo el módulo antes del Pull Request;
- cruzar documentación, código y base de datos;
- preparar un informe completo;
- revisar muchos archivos y consolidar resultados.

No hace falta usarlo para preguntas pequeñas.

## 40. Codex

Está orientado al trabajo de software: escribir código, depurar, ejecutar pruebas, revisar cambios y trabajar con repositorios.

Tiene mucho sentido en implementación, pero no reemplaza el análisis funcional.

## 41. Qué es un agente

Un agente sirve cuando existe un flujo de trabajo **repetible** con objetivo, pasos y criterios claros.

Ejemplo:

```text
TRIGGER
  ↓
Leer Pull Request
  ↓
Revisar reglas del proyecto
  ↓
Ejecutar checklist
  ↓
Generar informe
```

## 42. ¿Son necesarios ahora?

**No.** Todavía estamos definiendo el proceso. Automatizar demasiado pronto solo añade complejidad.

## 43. Cuándo sí valdría la pena

Más adelante podría ser útil un agente para:

- revisión de Pull Requests;
- auditoría semanal;
- actualización de documentación;
- comprobación repetitiva de reglas de seguridad.

## 44. Qué es una skill

Una skill puede verse como una capacidad especializada y reutilizable: instrucciones, checklist, plantillas o procedimientos para realizar bien una clase de tarea.

Ejemplos conceptuales para este proyecto:

### Revisar CRUD PHP
- PDO;
- prepared statements;
- validaciones;
- errores;
- sin password plano;
- esquema respetado.

### Analizar SQL
- PK;
- FK;
- UNIQUE;
- CHECK;
- ENUM;
- índices;
- relaciones.

### Revisar historia de usuario
- actor;
- acción;
- beneficio;
- reglas;
- criterios;
- pruebas.

## 45. ¿Crear skills personalizadas ahora?

No es prioritario. Primero conviene convertir buenas prácticas en Markdown y checklists. Cuando el proceso se repita y sea estable, entonces sí tiene sentido automatizar.

```text
HACER MANUALMENTE
      ↓
DOCUMENTAR
      ↓
REPETIR
      ↓
ESTABILIZAR
      ↓
AUTOMATIZAR
```

---

# PARTE XII — HERRAMIENTA SEGÚN LA TAREA

| Necesidad | Recomendación |
|---|---|
| Entender PHP/SQL | Chat |
| Diseñar reglas | Chat |
| Revisar repositorio | GitHub conectado |
| Programar/refactorizar | Codex o Chat técnico |
| Auditar muchos archivos | Work |
| Repetir flujo estable | Agente |
| Aplicar checklist especializado | Skill |
| Guardar conocimiento | Markdown en `/docs` |

---

# PARTE XIII — MARKDOWN VS LATEX

## 46. Markdown

Mejor durante el desarrollo porque:

- cambia con frecuencia;
- funciona bien con GitHub;
- es fácil de revisar en diffs;
- varios integrantes pueden editarlo;
- sirve como fuente de verdad viva.

## 47. LaTeX

Mejor para:

- informe final;
- versión académica;
- PDF formal;
- maquetación controlada.

## 48. Recomendación

Durante el proyecto:

```text
Markdown → fuente viva
```

Al final:

```text
Markdown → LaTeX/PDF si hace falta
```

---

# PARTE XIV — PLAN INMEDIATO

## Estado de avance

### Requisitos y lógica

- [x] Localizar y analizar `database.sql`.
- [x] Documentar `usuarios` y `roles`.
- [x] Confirmar alcance: CRUD de `usuarios`.
- [x] Confirmar `roles` como catálogo.
- [x] Confirmar que no hay CRUD de roles.
- [x] Confirmar que no hay login ni permisos.
- [x] Resolver huecos de lógica.
- [x] Refinar historias, criterios, mensajes y pruebas.
- [x] Construir trazabilidad.

### Preparación técnica base

- [x] Clonar el repositorio oficial.
- [x] Confirmar la rama operativa real: `dev/grupo1`.
- [x] Sincronizar `dev/grupo1` con el estado actual de `main` sin trabajar directamente sobre `main`.
- [x] Configurar autenticación Git/GitHub para `push`.
- [x] Revisar la estructura real del repositorio.
- [x] Instalar y comprobar XAMPP, Apache, PHP y phpMyAdmin.
- [x] Importar `database.sql` y reproducir/documentar `INC-BD-03` sin modificar el SQL.
- [x] Confirmar `roles` y `usuarios` con sus datos iniciales.
- [x] Confirmar acceso con `residencia_app`.
- [x] Confirmar disponibilidad de PDO + `pdo_mysql`.
- [x] Crear y probar `Admin/Config/ConexionPDO.php`.
- [x] Probar el flujo completo Navegador → Apache → PHP → PDO → `residencia_app` → base de datos.
- [x] Preparar la estructura mínima `Admin/modelos`, `Admin/ajax` y `Admin/vistas`.

## Siguiente secuencia

1. Definir el flujo Git interno para seis integrantes sin conflictos.
2. Realizar onboarding técnico común del equipo.
3. Confirmar que cada integrante puede clonar/actualizar el repositorio, ubicarse en la rama correcta y levantar su entorno.
4. Definir qué archivos son compartidos y cómo se integrarán cambios concurrentes.
5. Entregar las fichas funcionales completas.
6. Repartir HU-01, HU-02, HU-03 y HU-04 según `07_onboarding_y_reparto_equipo.md`.
7. Implementar en paralelo con revisión cruzada.
8. Integrar una historia por vez.
9. Ejecutar `05_pruebas.md`.
10. Preparar README, capturas y Pull Request.

**No iniciar implementación paralela hasta completar el flujo Git interno y el onboarding técnico del equipo.**

---

# PARTE XV — CHECKLIST DEL COORDINADOR

```text
[ ] ¿Existe un requisito?
[ ] ¿Existe una historia de usuario?
[ ] ¿Conocemos las reglas aplicables?
[ ] ¿El código usa columnas reales?
[ ] ¿Respeta database.sql?
[ ] ¿Usa prepared statements?
[ ] ¿Tiene validación?
[ ] ¿Maneja errores?
[ ] ¿Tiene prueba?
[ ] ¿Se entiende el commit?
[ ] ¿Está en la rama correcta?
[ ] ¿La documentación necesita actualizarse?
```

---

# PARTE XVI — PRINCIPIOS

1. No empezar por el código.
2. No inventar estructura de base de datos.
3. No confundir “funciona” con “cumple requisitos”.
4. No dejar decisiones importantes enterradas en chats.
5. No delegar archivos: delegar funcionalidades.
6. No automatizar un proceso que todavía no entendemos.
7. Usar IA para acelerar, no para reemplazar control humano.
8. Mantener una fuente de verdad actualizada.
9. Probar antes de integrar.
10. Todo integrante debe comprender el flujo general.

---

# PARTE XVII — PENDIENTES ACTUALES

## Requisitos y lógica

- [x] Análisis de base de datos.
- [x] Alcance funcional.
- [x] Historias, reglas y criterios.
- [x] Mensajes, flujos y pruebas.
- [x] Baseline V1 cerrada.

## Preparación técnica base

- [x] Repositorio oficial clonado.
- [x] Rama vigente `dev/grupo1` confirmada.
- [x] XAMPP y servicios funcionando.
- [x] `database.sql` importado para el trabajo del Grupo 1.
- [x] `residencia_app` comprobado.
- [x] PDO comprobado.
- [x] `ConexionPDO.php` creado y probado desde navegador.
- [x] Estructura base de `modelos`, `ajax` y `vistas` preparada.

## Coordinación del equipo — siguiente fase

- [ ] Definir estrategia Git para seis integrantes: ramas individuales, actualización e integración.
- [ ] Hacer onboarding común del equipo.
- [ ] Confirmar entorno funcional de cada integrante.
- [ ] Definir reglas sobre archivos compartidos y resolución de conflictos.
- [ ] Preparar una plantilla visual común cuando corresponda.
- [ ] Entregar una ficha funcional a cada integrante.
- [ ] Repartir las historias.
- [ ] Recién entonces iniciar implementación paralela.

Los detalles de la preparación técnica ya realizada quedan en `08_preparacion_tecnica.md`.

---

## Nota de mantenimiento

Cada vez que se tome una decisión importante, debe añadirse a este documento o al registro de decisiones.

Este documento no debería convertirse en un lugar para copiar código completo. Su objetivo es conservar **contexto, decisiones, reglas, arquitectura y método de trabajo**.
