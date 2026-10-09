# 🧪 Cómo probar el Módulo de Actividades (Grupo 6)

Guía paso a paso para probar el sistema en tu computadora con XAMPP.

---

## 0. Antes de empezar (verificar que todo esté listo)

1. Abre el **Panel de Control de XAMPP** y asegúrate de que estén en verde (Start):
   - ✅ **Apache**
   - ✅ **MySQL**
2. Verifica que la base de datos esté importada: entra a
   <http://localhost/phpmyadmin> → debe existir la base **`sistema_residencia`**
   con las tablas `actividades` y `participaciones`.
   - Si NO existe: en phpMyAdmin → pestaña **Importar** → selecciona el `database.sql`
     del docente → **Continuar**. Eso crea todo (incluido el usuario `residencia_app`).

### 🔗 Dirección para abrir el sistema
```
http://localhost/sistema-gestion-adultos-mayores/Admin/actividades/index.php
```
> Ya se dejó un acceso configurado en XAMPP apuntando a esta carpeta. Si abrieras el
> proyecto desde otra ruta, la dirección cambiará según dónde lo copies dentro de `htdocs`.

---

## 1. Recorrido rápido (para ver que todo carga)

| # | Acción | Qué deberías ver |
|---|---|---|
| 1 | Abre la dirección de arriba | La pantalla **Listado** con las 12 actividades de ejemplo |
| 2 | Clic en **Reporte** (menú arriba) | Tabla de indicadores (inscritos, asistieron, %) |
| 3 | Clic en el nombre de una actividad | Pantalla de **Detalle** con sus participantes |
| 4 | Clic en **Nueva actividad** | El **Formulario** vacío |

Si estas 4 pantallas cargan sin errores, el sistema está funcionando. 👍

---

## 2. Pruebas de las funciones principales (lo que evalúa la rúbrica)

> 💡 Como todas las actividades de ejemplo son de 2024 (ya pasaron), para probar
> **crear/inscribir** conviene usar una actividad **con fecha futura** que tú crees.

### ✅ Prueba A — Crear una actividad (Create)
1. Menú → **Nueva actividad**.
2. Llena: Nombre = `Taller de prueba`, Fecha = **una fecha futura** (ej. el próximo mes),
   Hora inicio = `09:00`, Hora fin = `10:00`, Lugar = `Sala 1`, Cupo = `2`, Responsable = cualquiera.
3. Clic en **Programar actividad**.
4. **Resultado esperado:** mensaje verde *"Actividad programada correctamente"* y te lleva al detalle.

### ✅ Prueba B — Validaciones (que NO deje guardar datos malos)
En **Nueva actividad**, prueba a propósito estos errores (uno por vez):
| Qué pones | Mensaje esperado |
|---|---|
| Hora fin `09:00` menor/igual que inicio `10:00` | *"La hora de fin debe ser posterior a la hora de inicio."* |
| Cupo = `0` o `-1` | *"El cupo debe ser un número entero mayor que 0."* |
| Fecha del año pasado | *"La actividad debe programarse para una fecha y hora futuras."* |
| Dejar el Nombre vacío | *"El campo Nombre es obligatorio."* |

### ✅ Prueba C — Inscribir residentes y control de cupo (Read/Create)
1. Entra al **Detalle** de la actividad que creaste (cupo 2).
2. En "Participantes", elige un residente y clic **Inscribir**. → mensaje verde con el cupo disponible.
3. Inscribe a un segundo residente. → cupo disponible llega a **0**.
4. Intenta inscribir a un tercero. → **Resultado esperado:** *"No hay cupo disponible en esta actividad."*
5. Intenta inscribir de nuevo al mismo residente. → *"El residente ya está inscrito en esta actividad."*

### ✅ Prueba D — Anular una inscripción
1. En el mismo Detalle, junto a un inscrito, clic en el botón **anular** (ícono de persona −).
2. Confirma. → **Resultado esperado:** *"Inscripción anulada. Se liberó un cupo."*

### ✅ Prueba E — Editar una actividad (Update)
1. En el Detalle de una actividad **programada**, clic en **Editar**.
2. Cambia el lugar o el cupo y guarda.
3. **Resultado esperado:** *"Actividad actualizada correctamente."*
   - Nota: si bajas el cupo por debajo de los ya inscritos, te lo impedirá con un aviso.

### ✅ Prueba F — Cancelar (baja lógica / Delete) y reactivar
1. En el Detalle de una actividad **programada**, clic en **Cancelar actividad** y confirma.
2. **Resultado esperado:** *"Actividad cancelada..."* y el estado pasa a **CANCELADA**.
3. Verás un botón **Reactivar** para volverla a PROGRAMADA (si aún es futura).
   - Ojo: en una actividad cancelada **ya no se puede inscribir** (así se prueba la regla).

### ✅ Prueba G — Registrar asistencia
> La asistencia solo se habilita cuando la actividad **ya empezó**.
1. Para probarlo rápido, crea una actividad que **empiece en 2–3 minutos** (misma fecha de hoy,
   hora de inicio unos minutos adelante) e **inscribe** a 2 residentes.
2. Espera a que pase la hora de inicio y entra a **Asistencia** (ícono ✓ en el listado o botón en el detalle).
3. Marca a uno **Asistió** y a otro **No asistió**, deja alguno sin marcar, y **Guardar todo**.
4. **Resultado esperado:** *"Asistencia guardada: N registradas, M pendientes."*
   - Si intentas marcar asistencia **antes** de que empiece, dirá que solo se puede desde el inicio.

### ✅ Prueba H — Buscar y filtrar (listado)
1. En **Listado**, escribe en el buscador (ej. `Taller`) y filtra por estado o fecha.
2. **Resultado esperado:** la tabla se reduce a lo que coincide.

### ✅ Prueba I — Reporte
1. Menú → **Reporte**. Filtra por fecha o nombre.
2. **Resultado esperado:** indicadores por actividad; cuando no hay asistencia marcada, el
   **% Asistencia** muestra "—" (nunca un error).

---

## 3. Pruebas de seguridad (opcional, para la sustentación)

| Prueba | Cómo | Resultado esperado |
|---|---|---|
| **XSS** | Crea una actividad con nombre `<script>alert(1)</script>` | Se guarda y se muestra como **texto**, no se ejecuta ninguna alerta |
| **Datos fuera de rango** | En el listado, filtra con "desde" mayor que "hasta" | Aviso: *"La fecha 'desde' no puede ser posterior a la fecha 'hasta'."* |

---

## 4. Si algo falla

| Problema | Solución |
|---|---|
| "No se pudo conectar a la base de datos" | Revisa que **MySQL** esté encendido en XAMPP y que el `database.sql` esté importado. |
| Página en blanco o error | Verifica que **Apache** esté encendido y que la dirección sea la correcta. |
| "Access denied for user 'residencia_app'" | El usuario no está creado o tiene otra clave. Reimporta el `database.sql` (crea el usuario), o revisa `config/conexion.php`. |
| Las actividades de ejemplo salen como "FINALIZADA" | Es normal: son del 2024. Crea actividades con fecha futura para probar. |

---

## 5. Restaurar los datos de ejemplo (dejar todo como al inicio)

Si hiciste muchas pruebas y quieres volver al estado original, reimporta el `database.sql`
del docente en phpMyAdmin (Importar). Eso borra tus pruebas y deja los datos de ejemplo tal cual.
