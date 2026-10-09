# 08 — Preparación técnica — Grupo 1

> **Proyecto:** Sistema de Gestión de Residencia para Adultos Mayores  
> **Módulo:** CRUD de `usuarios` con `roles` como catálogo  
> **Estado:** PREPARACIÓN TÉCNICA BASE COMPLETADA  
> **Propósito:** registrar el entorno y las comprobaciones realizadas antes de repartir las historias de usuario.

---

# 1. Estado alcanzado

Se completó la base técnica necesaria para comenzar la coordinación del trabajo paralelo. Todavía no se ha implementado HU-01, HU-02, HU-03 ni HU-04.

```text
Requisitos y lógica            ✅
Repositorio / Git              ✅
Entorno web local              ✅
Base de datos                  ✅ para Grupo 1
Usuario técnico de BD          ✅
PDO                            ✅
Estructura mínima              ✅
Flujo Git de los 6             ⏳ siguiente
Reparto de historias           ⏳
CRUD                           ❌ aún no
```

---

# 2. Repositorio y Git

## Repositorio

```text
danielalva2008/sistema-gestion-adultos-mayores
```

## Rama operativa vigente

```text
dev/grupo1
```

El documento original menciona `feature/grupo-1-usuarios`, pero el docente creó/señaló `dev/grupo1` y el repositorio real contiene ramas `dev/grupo1`, `dev/grupo2`, etc. Por ello `dev/grupo1` es la rama vigente del Grupo 1.

Reglas actuales:

- no trabajar directamente en `main`;
- `dev/grupo1` está sincronizada y se utiliza como rama compartida del grupo;
- antes de que seis personas programen en paralelo se definirá el flujo de ramas individuales e integración;
- GitHub CLI quedó configurado en el equipo del coordinador para autenticar `push`.

## Ruta local del coordinador

```text
/opt/lampp/htdocs/sistema-residencia
```

La ruta se eligió para que Apache pueda servir el repositorio directamente. Un enlace simbólico desde `htdocs` hacia `/home/...` produjo HTTP 403 por permisos del directorio personal; no se usaron permisos inseguros para resolverlo.

Esta ruta es específica del equipo Linux del coordinador y no obliga a que todos los integrantes usen la misma ubicación.

---

# 3. Entorno local verificado

Equipo del coordinador:

- Sistema operativo: CachyOS Linux.
- XAMPP: 8.2.12.
- Apache: funcionando.
- PHP: 8.2.12.
- Servidor de base incluido en XAMPP: MariaDB 10.4.32.
- phpMyAdmin: funcionando.

El proyecto académico conserva MySQL 8.x como referencia oficial. La presencia de MariaDB en el XAMPP de Linux se trata únicamente como una característica del entorno local y no autoriza cambios en `database.sql`.

---

# 4. Base de datos

Se importó el `database.sql` oficial sin modificarlo.

Se comprobó la existencia de las nueve tablas documentadas, incluyendo las relevantes para Grupo 1:

```text
roles      ✅
usuarios   ✅
```

## Datos iniciales comprobados

Roles:

```text
1  ADMINISTRADOR  ACTIVO
2  SUPERVISOR     ACTIVO
3  PERSONAL       ACTIVO
```

Usuario inicial:

```text
username: admin
id_rol:   1
estado:   ACTIVO
```

## Incidencia reproducida

Durante la importación apareció `INC-BD-03`: una fila del `INSERT INTO incidentes` contiene 9 valores para 10 columnas.

Tratamiento aplicado:

- no inventar el valor faltante;
- no modificar silenciosamente `database.sql`;
- continuar con Grupo 1 porque `roles`, `usuarios` y la cuenta técnica necesaria ya fueron creados correctamente;
- consultar al docente si la incidencia llega a bloquear una tarea futura.

---

# 5. Usuario técnico de la aplicación

Se comprobó directamente la cuenta:

```text
residencia_app@localhost
```

La contraseña y definición exacta permanecen en `database.sql`; no se duplican en este documento.

La cuenta pudo consultar `roles` y `usuarios` dentro de `sistema_residencia`.

Debe distinguirse de:

```text
residencia_app  → cuenta técnica PHP → base de datos
admin           → registro funcional de la tabla usuarios
```

El CRUD deberá utilizar `residencia_app`, no `root`.

---

# 6. PDO

El PHP de XAMPP dispone de los controladores:

```text
mysql
pgsql
sqlite
```

Por tanto `pdo_mysql` está disponible para el proyecto.

Se creó:

```text
Admin/Config/ConexionPDO.php
```

La conexión fue comprobada primero desde PHP por terminal y después desde Apache/navegador.

Flujo validado:

```text
Navegador
   ↓
Apache
   ↓
PHP
   ↓
PDO
   ↓
residencia_app
   ↓
sistema_residencia
```

Resultado: conexión correcta.

La configuración previa `Admin/Config/Conexion.php` y `global.php` no se modificó para evitar afectar infraestructura existente del repositorio.

---

# 7. Estructura mínima del módulo

La base preparada es:

```text
Admin/
├── Config/
│   ├── Conexion.php
│   ├── global.php
│   └── ConexionPDO.php
├── modelos/
│   └── Usuario.php
├── ajax/
│   └── usuario.php
└── vistas/
    └── usuario.php
```

Responsabilidades conceptuales:

```text
vistas/usuario.php
        ↓ interfaz
ajax/usuario.php
        ↓ entrada/control de operaciones
modelos/Usuario.php
        ↓ acceso a datos del módulo
ConexionPDO.php
        ↓
Base de datos
```

`Usuario.php`, `ajax/usuario.php` y `vistas/usuario.php` permanecen como estructura mínima. Las operaciones concretas se añadirán historia por historia.

---

# 8. Base compartida entre historias

HU-01, HU-02, HU-03 y HU-04 compartirán:

- `ConexionPDO.php`;
- el modelo `Usuario.php`;
- el punto de entrada `ajax/usuario.php`;
- la vista base `vistas/usuario.php`;
- las restricciones de `database.sql`;
- reglas RN-19, RN-20 y RN-21 cuando correspondan;
- el usuario técnico `residencia_app`.

No se crearán cuatro conexiones ni cuatro arquitecturas independientes.

---

# 9. Qué todavía no se ha implementado

No se ha implementado todavía:

- registro de usuarios;
- listado o búsqueda;
- edición;
- inactivación/reactivación;
- CRUD de roles;
- login;
- permisos;
- sistema de sesiones.

La ausencia de las cuatro primeras operaciones es intencional: el proyecto todavía se encuentra antes del reparto funcional. Las restantes están fuera de alcance.

---

# 10. Siguiente fase

El siguiente bloque de trabajo es de coordinación, no de CRUD:

1. definir cómo trabajarán seis integrantes con Git sin pisarse;
2. decidir ramas individuales y estrategia de integración;
3. establecer comandos básicos y rutina diaria;
4. identificar archivos compartidos con mayor riesgo de conflicto;
5. realizar onboarding técnico;
6. entregar fichas HU;
7. comenzar una historia por vez durante la integración.

Solo después de completar estos puntos comienza la implementación paralela.

---

# 11. Checklist para reproducir la base en otro equipo

```text
[ ] Clonar el repositorio oficial
[ ] Ubicarse en la rama indicada por coordinación
[ ] Instalar/iniciar entorno PHP + Apache + MySQL/MariaDB compatible
[ ] Importar database.sql sin modificarlo
[ ] Confirmar roles y usuarios
[ ] Confirmar residencia_app
[ ] Confirmar PDO/pdo_mysql
[ ] Obtener la estructura base actualizada desde Git
[ ] Abrir la vista base mediante el servidor web
[ ] No empezar CRUD hasta recibir una ficha funcional
```

---

# 12. Evidencia de cierre de preparación técnica

Se considera cerrada esta fase porque se verificó:

```text
Repositorio → rama correcta → servidor web → PHP → PDO → usuario técnico → base de datos
```

y la base común quedó disponible en Git para el Grupo 1.

La siguiente fuente operativa para el equipo es `07_onboarding_y_reparto_equipo.md`, complementada por este documento.
