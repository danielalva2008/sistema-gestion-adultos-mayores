<?php
// Pruebas CLI aisladas: las tablas TEMPORARY desaparecen al cerrar esta conexión.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../modelos/Habitacion.php';
$db = new PDO('mysql:host=127.0.0.1;dbname=sistema_residencia;charset=utf8mb4', getenv('TEST_DB_USER') ?: 'root', getenv('TEST_DB_PASSWORD') ?: '', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false]);
foreach (['habitaciones', 'residentes'] as $tabla) {
    $ddl = $db->query('SHOW CREATE TABLE '.$tabla)->fetch(PDO::FETCH_NUM)[1];
    // Los nombres de tabla son constantes de esta prueba; quitar FK solo en la copia temporal.
    $ddl = preg_replace('/,\n  CONSTRAINT `fk_[^\n]+/', '', $ddl);
    $db->exec(str_replace('CREATE TABLE', 'CREATE TEMPORARY TABLE', $ddl));
}
$m = new Habitacion($db); $n = 0;
function comprobar(bool $ok, string $name): void { global $n; if (!$ok) { throw new RuntimeException($name); } echo 'OK '.++$n.' '.$name."\n"; }
function rechaza(callable $f, string $name): void { try { $f(); } catch (DomainException|PDOException $e) { comprobar(true, $name); return; } comprobar(false, $name); }
$d=['numero'=>'PRUEBA','piso'=>'1','capacidad'=>'2','estado'=>'DISPONIBLE','observaciones'=>'Prueba aislada'];
$m->guardar(null,$d); $id=(int)$m->listar()[0]['id_habitacion'];
comprobar(count($m->listar())===1,'Crear y listar');
comprobar($m->obtener($id)['numero']==='PRUEBA','Obtener detalle');
comprobar(count($m->listar('PRU','DISPONIBLE','1'))===1,'Filtros combinados');
comprobar(count($m->listar("' OR 1=1 --"))===0,'Búsqueda SQL parametrizada');
rechaza(fn()=>$m->guardar(null,$d),'Número duplicado');
foreach (['0','-1','1.5','texto','2147483648'] as $bad) {
 foreach(['piso','capacidad'] as $field) { $badData=$d; $badData[$field]=$bad; rechaza(fn()=>$m->guardar(null,$badData),"Validar $field = $bad"); }
}
foreach (['numero'=>'','estado'=>'INVALIDO','observaciones'=>str_repeat('á',256)] as $field=>$bad) { $badData=$d; $badData[$field]=$bad; rechaza(fn()=>$m->guardar(null,$badData),'Validar '.$field); }
$d['estado']='MANTENIMIENTO'; $m->guardar($id,$d); comprobar($m->obtener($id)['estado']==='MANTENIMIENTO','Editar estado');
$d['estado']='OCUPADA'; $m->guardar($id,$d); rechaza(fn()=>$m->eliminar($id),'Bloquear OCUPADA sin residentes');
$d['estado']='DISPONIBLE'; $m->guardar($id,$d);
$stmt=$db->prepare("INSERT INTO residentes(id_habitacion,codigo_residente,nombres,apellidos,sexo,fecha_nacimiento,estado_civil,fecha_ingreso,estado) VALUES (?,?,'Prueba','Sintética','F','1940-01-01','SOLTERO','2026-01-01','ACTIVO')");
$stmt->execute([$id,'TEST1']); $stmt->execute([$id,'TEST2']);
rechaza(fn()=>$m->eliminar($id),'Bloquear residentes activos aunque estado sea DISPONIBLE');
$badData=$d; $badData['capacidad']='1'; rechaza(fn()=>$m->guardar($id,$badData),'Bloquear reducción por debajo de ocupación');
comprobar((int)$m->obtener($id)['capacidad']===2,'Rollback mantiene capacidad');
$db->exec("UPDATE residentes SET estado='EGRESADO'");
$m->eliminar($id); comprobar(count($m->listar())===0,'Eliminar habitación libre');
rechaza(fn()=>$m->obtener($id),'Detectar registro inexistente');
echo "$n comprobaciones correctas. Ningún registro persistente modificado.\n";
