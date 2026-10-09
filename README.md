# Módulo de Incidentes — Grupo 5

**Sistema de Gestión de Residencia para Adultos Mayores**  
Curso: Desarrollo de Plataformas  
Tecnología: PHP 8.x + MySQL 8.x + XAMPP  
Branch: `feature/grupo-5-incidentes`

---

## Integrantes del grupo

| # | Nombres y apellidos | Código / correo |
|---|---------------------|-----------------|
| 1 | *(completar)*       |                 |
| 2 | *(completar)*       |                 |
| 3 | *(completar)*       |                 |
| 4 | *(completar)*       |                 |
| 5 | *(completar)*       |                 |
| 6 | *(completar)*       |                 |

---

## Tablas utilizadas

| Tabla              | Uso                                      |
|--------------------|------------------------------------------|
| `incidentes`       | Registro y seguimiento de incidentes     |
| `tipos_incidente`  | Catálogo de tipos + nivel de riesgo      |
| `residentes`       | FK (solo lectura / select)               |
| `personal`         | FK (quien reporta, solo lectura/select)  |

Vista de apoyo: `vw_incidentes_residentes`

---

## Historias de usuario implementadas

1. **Como personal**, quiero registrar un incidente indicando residente, tipo, fecha/hora, lugar y descripción, para dejar constancia de lo ocurrido.
2. **Como supervisor**, quiero listar los incidentes filtrando por residente, tipo o nivel de riesgo, para priorizar el seguimiento.
3. **Como personal**, quiero actualizar el estado de un incidente (REGISTRADO, EN_SEGUIMIENTO, CERRADO) y registrar la acción realizada, para documentar su resolución.
4. **Como supervisor**, quiero consultar los tipos de incidente y su nivel de riesgo (BAJO, MEDIO, ALTO, CRITICO), para mantener catalogado el riesgo institucional.

**Regla de negocio clave:** `fecha_cierre` solo se registra cuando el estado es `CERRADO`.

---

## Requisitos previos

- XAMPP (PHP 8.x + MySQL 8.x)
- Navegador moderno
- El script `databaseadultomayor.sql` (o `database.sql`) importado

---

## Cómo importar la base de datos

1. Abre **phpMyAdmin**: http://localhost/phpmyadmin
2. Ve a la pestaña **Importar**
3. Selecciona el archivo `databaseadultomayor.sql`
4. Clic en **Continuar / Ejecutar**
5. Verifica que se creó la base `sistema_residencia` y el usuario `residencia_app`

**Credenciales de aplicación (definidas en el SQL):**

- Usuario: `residencia_app`
- Contraseña: `Residencia2026*`

---

## Cómo configurar y ejecutar el módulo

### 1. Copiar el módulo

Copia la carpeta `modulo-incidentes` dentro de la carpeta de tu proyecto en XAMPP, por ejemplo:

```
C:\xampp\htdocs\sistema-gestion-adultos-mayores\modulo-incidentes\
```

o

```
/opt/lampp/htdocs/sistema-gestion-adultos-mayores/modulo-incidentes/
```

### 2. Verificar la conexión

El archivo `config/conexion.php` ya está configurado con:

```php
DB_HOST = localhost
DB_NAME = sistema_residencia
DB_USER = residencia_app
DB_PASS = Residencia2026*
```

Si cambias la contraseña del usuario en MySQL, actualiza solo ese archivo.

### 3. Abrir en el navegador

```
http://localhost/sistema-gestion-adultos-mayores/modulo-incidentes/
```

o directamente:

```
http://localhost/sistema-gestion-adultos-mayores/modulo-incidentes/incidentes/index.php
```

### 4. Navegación del módulo

| Sección              | URL relativa              |
|----------------------|---------------------------|
| Listado de incidentes| `/incidentes/index.php`   |
| Crear incidente      | `/incidentes/crear.php`   |
| Editar incidente     | `/incidentes/editar.php?id=X` |
| Ver detalle          | `/incidentes/ver.php?id=X`|
| Tipos de incidente   | `/tipos/index.php`        |
| Crear tipo           | `/tipos/crear.php`        |

---

## Estructura de carpetas

```
modulo-incidentes/
├── config/
│   └── conexion.php          ← PDO + residencia_app
├── includes/
│   ├── header.php
│   └── footer.php
├── incidentes/
│   ├── index.php             ← Listado + filtros
│   ├── crear.php
│   ├── editar.php
│   ├── ver.php
│   └── eliminar.php
├── tipos/
│   ├── index.php
│   ├── crear.php
│   ├── editar.php
│   └── eliminar.php
├── index.php                 ← Redirección al listado
└── README.md
```

---

## Funcionalidades implementadas

### Incidentes
- **Create**: formulario con selects de residente, tipo y personal
- **Read**: listado con filtros (residente, tipo, nivel de riesgo, estado)
- **Update**: precarga de datos + misma validación de fecha_cierre
- **Delete**: eliminación física (con confirmación)
- Vista de detalle completa
- Validación de la regla: `fecha_cierre` solo si estado = CERRADO

### Tipos de incidente
- CRUD completo
- Búsqueda por nombre / nivel / descripción
- Badges de color según nivel de riesgo
- Protección de eliminación si hay incidentes asociados (FK)

### Técnicas
- PDO con consultas preparadas (sin concatenación de SQL)
- Usuario de aplicación `residencia_app` (no root)
- Bootstrap 5 + Bootstrap Icons
- Mensajes de éxito / error claros
- Código organizado por carpetas

---

## Flujo Git recomendado

```bash
git checkout -b feature/grupo-5-incidentes
# ... desarrollar ...
git add modulo-incidentes/
git commit -m "feat: CRUD completo de incidentes y tipos_incidente"
git push -u origin feature/grupo-5-incidentes
# Abrir Pull Request hacia main
```

---

## Capturas de pantalla

*(Adjuntar aquí las capturas del CRUD funcionando: listado, filtros, crear, editar, tipos, etc.)*

---

## Notas técnicas

- Las fechas del formulario `datetime-local` se convierten a formato MySQL (`Y-m-d H:i:s`).
- Si el estado no es `CERRADO`, `fecha_cierre` se fuerza a `NULL`.
- La eliminación de un tipo falla con mensaje claro si tiene incidentes asociados (restricción FK).
- Se usa la vista conceptual y JOINs explícitos para el listado enriquecido.
