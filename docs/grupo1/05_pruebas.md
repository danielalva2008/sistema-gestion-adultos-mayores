# 05 — Plan de pruebas y trazabilidad — Grupo 1

> **Módulo:** CRUD de `usuarios`  
> **Baseline asociada:** `01_requisitos.md` V1  
> **Objetivo:** demostrar que cada historia cumple reglas, criterios de aceptación y restricciones del modelo.

---

## 1. Principio de pruebas

No se considera terminada una funcionalidad solo porque “parece funcionar”.

Cada historia debe probar:

1. caso correcto;
2. campos obligatorios;
3. restricciones de unicidad;
4. datos inválidos;
5. relaciones con `roles`;
6. cambios de estado;
7. comportamiento de contraseña;
8. mensajes al usuario;
9. persistencia correcta en MySQL;
10. controles técnicos transversales.

---

## 2. Precondiciones generales

Antes de ejecutar las pruebas:

- MySQL está activo en XAMPP;
- `database.sql` fue importado;
- existe la tabla `usuarios`;
- existe la tabla `roles`;
- los roles iniciales de prueba están disponibles;
- la aplicación puede conectarse con el usuario MySQL indicado por el docente;
- se conoce el estado inicial de los datos usados en cada prueba.

Cuando una prueba cree datos temporales, estos deberán identificarse claramente para evitar confundirlos con los datos iniciales.

---

# 3. Casos de prueba — HU-01 Registrar usuario

| ID | Caso | Datos/acción principal | Resultado esperado |
|---|---|---|---|
| `CP-01` | Registro válido | Todos los obligatorios válidos + rol permitido | Usuario guardado y mensaje de éxito |
| `CP-02` | Username repetido | Usar un username ya existente | Se rechaza; `MSG-05` |
| `CP-03` | Email repetido | Usar un email ya existente | Se rechaza; `MSG-06` |
| `CP-04` | Email vacío | Dejar email sin valor | Registro permitido; email se trata como ausencia de valor |
| `CP-05` | Email con formato inválido | Ej. texto sin formato de correo | Se rechaza; `MSG-07` |
| `CP-06` | Falta un obligatorio | Omitir nombre, apellido, username, contraseña o rol | Se rechaza; `MSG-08` o `MSG-15` según corresponda |
| `CP-07` | Username excede 50 caracteres | Enviar valor demasiado largo | Se rechaza antes de guardar |
| `CP-08` | Nombres/apellidos exceden 100 caracteres | Enviar valor demasiado largo | Se rechaza antes de guardar |
| `CP-09` | Email excede 120 caracteres | Enviar valor demasiado largo | Se rechaza antes de guardar |
| `CP-10` | Rol inexistente | Alterar el valor enviado del selector | Se rechaza; `MSG-09` |
| `CP-11` | Rol inactivo | Enviar un rol no permitido para nueva asignación | Se rechaza; `MSG-09` |
| `CP-12` | Revisar contraseña almacenada | Consultar el registro luego de crear | `password_hash` contiene hash, no texto plano |
| `CP-13` | Valores automáticos | Crear usuario normal | Estado `ACTIVO`; fecha generada por MySQL |

### Evidencias mínimas HU-01

- captura del formulario;
- captura del mensaje de registro correcto;
- consulta o vista de BD que demuestre existencia del usuario;
- evidencia de que la contraseña no quedó en texto plano;
- evidencia de un intento duplicado rechazado.

---

# 4. Casos de prueba — HU-02 Listar y buscar

| ID | Caso | Acción | Resultado esperado |
|---|---|---|---|
| `CP-14` | Abrir listado | Entrar al módulo sin filtros | Aparecen todos los usuarios |
| `CP-15` | Buscar por nombres | Introducir nombre existente | Coincidencias correctas |
| `CP-16` | Buscar por apellidos | Introducir apellido existente | Coincidencias correctas |
| `CP-17` | Buscar por username | Introducir username existente | Coincidencia correcta |
| `CP-18` | Filtrar activos | Estado = `ACTIVO` | Solo usuarios activos |
| `CP-19` | Filtrar inactivos | Estado = `INACTIVO` | Solo usuarios inactivos |
| `CP-20` | Combinar texto + estado | Aplicar ambos criterios | Solo registros que cumplen ambos |
| `CP-21` | Búsqueda inexistente | Buscar texto sin coincidencias | `MSG-12`, sin error técnico |
| `CP-22` | Mostrar rol | Revisar columna Rol | Se muestra `roles.nombre`, no únicamente `id_rol` |

---

# 5. Casos de prueba — HU-03 Editar usuario

| ID | Caso | Acción | Resultado esperado |
|---|---|---|---|
| `CP-23` | Editar datos válidos | Cambiar nombres/apellidos/email válidos | Datos actualizados |
| `CP-24` | Mantener propio username | Guardar sin cambiar username | Permitido |
| `CP-25` | Username de otro usuario | Cambiar al username ajeno | Se rechaza; `MSG-05` |
| `CP-26` | Mantener propio email | Guardar el mismo email | Permitido |
| `CP-27` | Email de otro usuario | Cambiar al email ajeno | Se rechaza; `MSG-06` |
| `CP-28` | Contraseña nueva vacía | Editar otros datos y dejarla vacía | El hash anterior permanece |
| `CP-29` | Cambiar contraseña | Introducir contraseña nueva | Se genera un hash nuevo |
| `CP-30` | Cambiar rol | Seleccionar otro rol permitido | `id_rol` actualizado |
| `CP-31` | Rol inválido | Manipular `id_rol` enviado | Se rechaza; `MSG-09` |
| `CP-32` | Reactivar usuario | `INACTIVO → ACTIVO` | Usuario activo y mensaje de éxito |
| `CP-33` | Estado inválido | Manipular valor fuera del ENUM permitido | Se rechaza; `MSG-10` |
| `CP-34` | ID inexistente | Abrir/guardar un `id_usuario` inexistente | `MSG-11` |

### Evidencia adicional HU-03

Comprobar que el formulario de edición **nunca** muestra el valor de `password_hash` como contraseña.

---

# 6. Casos de prueba — HU-04 Inactivar usuario

| ID | Caso | Acción | Resultado esperado |
|---|---|---|---|
| `CP-35` | Inactivar activo | Ejecutar baja sobre usuario `ACTIVO` | Estado cambia a `INACTIVO`; `MSG-03` |
| `CP-36` | Verificar persistencia | Consultar el usuario inactivado | El registro continúa en `usuarios` |
| `CP-37` | Revisar listado | Volver al listado | Usuario aparece como `INACTIVO` |
| `CP-38` | Reactivar posteriormente | Cambiar `INACTIVO → ACTIVO` | Usuario vuelve a activo; `MSG-04` |

---

# 7. Pruebas técnicas transversales

| ID | Verificación | Fuente | Resultado esperado |
|---|---|---|---|
| `PT-01` | Consultas preparadas/parametrizadas | DOCENTE + BUENA PRÁCTICA | Entradas de usuario no se concatenan directamente en SQL |
| `PT-02` | Validación en servidor | BUENA PRÁCTICA | Manipular el HTML no permite saltarse reglas críticas |
| `PT-03` | Contraseña segura en almacenamiento | DOCENTE | Nunca se almacena contraseña en texto plano |
| `PT-04` | Manejo de errores | DOCENTE + BUENA PRÁCTICA | No se muestra al usuario un error técnico completo de MySQL |
| `PT-05` | Usuario MySQL de aplicación | DOCENTE / SQL | El CRUD usa la cuenta técnica definida para la aplicación, no `root` |

---

# 8. Matriz de trazabilidad

| Historia | Aspecto | Reglas | Criterios | Pruebas |
|---|---|---|---|---|
| `HU-01` | Obligatorios | RN-01, RN-03, RN-06, RN-09 | CA-01.1, CA-01.2 | CP-06, CP-07, CP-08, CP-09 |
| `HU-01` | Username | RN-01 | CA-01.5 | CP-02, CP-07 |
| `HU-01` | Email | RN-02 | CA-01.3, CA-01.4, CA-01.6 | CP-03, CP-04, CP-05, CP-09 |
| `HU-01` | Rol | RN-03, RN-05, RN-18 | CA-01.7 | CP-10, CP-11 |
| `HU-01` | Password | RN-07, RN-09 | CA-01.8 | CP-12, PT-03 |
| `HU-01` | Estado/fecha | RN-10, RN-16 | CA-01.9, CA-01.10 | CP-13 |
| `HU-01` | Resultado | RN-21 | CA-01.11, CA-01.12 | CP-01 |
| `HU-02` | Listado | RN-17, RN-18 | CA-02.1, CA-02.9 | CP-14, CP-22 |
| `HU-02` | Búsqueda/filtro | RN-17 | CA-02.2 a CA-02.8 | CP-15 a CP-21 |
| `HU-03` | Datos | RN-01, RN-02, RN-06, RN-13 | CA-03.1, CA-03.2, CA-03.3, CA-03.10 | CP-23 a CP-27 |
| `HU-03` | Contraseña | RN-08 | CA-03.7, CA-03.8, CA-03.9 | CP-28, CP-29 |
| `HU-03` | Rol | RN-03, RN-05, RN-18 | CA-03.4 | CP-30, CP-31 |
| `HU-03` | Estado | RN-10, RN-12 | CA-03.5, CA-03.6 | CP-32, CP-33 |
| `HU-03` | Identificador | SQL | CA-03.1 | CP-34 |
| `HU-04` | Baja lógica | RN-10, RN-11 | CA-04.1 a CA-04.6 | CP-35, CP-36, CP-37 |
| `HU-03/HU-04` | Reactivación | RN-12 | CA-03.6 | CP-32, CP-38 |
| Todas | Servidor | RN-19 | criterios aplicables | PT-02 |
| Todas | SQL seguro | RN-20 | transversal | PT-01 |
| Todas | Mensajes | RN-21 | criterios de resultado | pruebas funcionales + PT-04 |

---

# 9. Registro de ejecución

Para cada prueba ejecutada se recomienda registrar:

| Campo | Contenido |
|---|---|
| Fecha | día de ejecución |
| Responsable | integrante que probó |
| ID de prueba | CP/PT correspondiente |
| Resultado | PASA / FALLA |
| Evidencia | captura, descripción o enlace |
| Observación | error encontrado |
| Corrección | commit o cambio asociado |

Ejemplo:

```text
CP-25 | FALLA | Permitió username duplicado
Corrección: validar contra otros usuarios excluyendo id_usuario actual
Commit: fix: validar username duplicado en edición
Reprueba: PASA
```

---

# 10. Criterio para integrar una historia

No se integra una historia a la versión del grupo hasta que:

- todos sus criterios relevantes estén cubiertos;
- sus pruebas principales pasen;
- no modifique el esquema oficial;
- no amplíe el alcance;
- los errores previsibles sean comprensibles;
- el responsable pueda explicar el flujo;
- otro integrante haya realizado una revisión básica.
