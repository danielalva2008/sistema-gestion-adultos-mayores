# Verificación completa del Grupo 3

Resultado guardado: 19 pruebas automatizadas aprobadas, 104 comprobaciones adicionales correctas y 21 peticiones curl correctas. Son tres conjuntos de verificación, no 144 pruebas independientes. Se comprobaron las rutas requeridas, fórmulas, filtros, CRUD, límites, errores y esquema real de SQL Server.

Para reproducir valores del Word se utilizaron bases de ensayo separadas con los datos originales: sistema_residencia_pruebas y residencia_sqlserver_pruebas. No se restaura el origen operativo ni se borran filas históricas del destino. La instancia de navegador temporal usó 5001; la aplicación habitual usa 5000.

Las pruebas de CRUD escriben solo temporales y los eliminan. La transferencia inserta o actualiza el destino seleccionado y no sincroniza borrados. Si el destino tiene filas históricas, su total puede ser superior al del origen. La prueba verifica que el total aumente únicamente por las inserciones informadas y que no se dupliquen IDs al repetir.

## Repetir en Linux con las bases de ensayo ya preparadas

    MYSQL_DATABASE=sistema_residencia_pruebas SQLSERVER_DATABASE=residencia_sqlserver_pruebas .venv/bin/python -m unittest discover -s tests -v
    MYSQL_DATABASE=sistema_residencia_pruebas SQLSERVER_DATABASE=residencia_sqlserver_pruebas .venv/bin/python tests/verificar_ampliado.py

## Repetir en PowerShell con sus bases de ensayo ya preparadas

    $env:MYSQL_DATABASE='sistema_residencia_pruebas'
    $env:SQLSERVER_DATABASE='residencia_sqlserver_pruebas'
    .venv\Scripts\python.exe -m unittest discover -s tests -v
    .venv\Scripts\python.exe tests/verificar_ampliado.py

Configurar antes los usuarios de aplicación de ese equipo. Las credenciales continúan en su .env. El script ampliado se niega a escribir si los nombres de ambas bases no terminan en _pruebas. Las variables de ensayo solo se aplican a esos procesos o a la terminal usada, no al servidor normal ya abierto.

El diagnóstico ordinario no ejecuta la suite ni escribe datos. Las bases de ensayo se preparan manualmente con los scripts de clase y del destino; no se crean ni se importan al arrancar la aplicación.

## Navegador

Se comprobaron filtros Enfermera + ACTIVO, creación, edición a INACTIVO y eliminación del temporal UI-G3-FINAL (ID 46), bloqueo de PER001 con referencias, las pestañas de distribución y el botón Transferir. El temporal se eliminó antes de transferir. La transferencia final se hizo en el destino de ensayo y devolvió 15 leídos, 0 insertados y 15 actualizados. Consultas directas verificaron campos, PK, ausencia de IDENTITY y de FK, y fecha_transferencia.

## Alcance y límites

Las pruebas incluyen SQL de búsqueda tratado como texto, consultas parametrizadas, validación de tipos y longitudes, JSON inválido y respuestas 200/201/204/400/404/405/409/503. El fallo interno de transferencia y la indisponibilidad del destino se simularon para comprobar rollback y 503. No se detuvieron servicios operativos para simular averías.

Los datos operativos cambiaron durante la revisión. Las referencias numéricas del informe y las capturas de ensayo son del conjunto original, no una promesa de totales fijos en la aplicación normal. La nueva prueba no borró esos cambios ni las filas antiguas que existen solo en SQL Server.

Linux fue verificado realmente. Las comprobaciones de rutas y drivers de Windows son simulaciones; no sustituyen una ejecución en un equipo Windows real. No es una auditoría exhaustiva de seguridad ni de carga concurrente.

Evidencias en docs/evidencias: pruebas_mysql8.txt, verificacion_completa.json, verificacion_navegador.json, curl_resultados.json y las capturas. Informe final: docs/Informe_Grupo3_Personal.pdf.
