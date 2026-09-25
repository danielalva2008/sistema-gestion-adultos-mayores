<?php
// Solo CLI; todas las escrituras de prueba se revierten.
if (PHP_SAPI!=='cli') {http_response_code(404);exit;}
require __DIR__.'/config.php';
require __DIR__.'/model.php';
function check(bool $ok,string $label): void {if (!$ok) throw new RuntimeException($label);echo "OK: $label\n";}
$db=database(); $db->beginTransaction();
try {
    $input=['codigo_personal'=>'TEST-'.bin2hex(random_bytes(4)), 'nombres'=>'María','apellidos'=>'Prueba','cargo'=>'Enfermera','especialidad'=>'Geriatría','telefono'=>'+51 999 111 222','email'=>'prueba@example.test','fecha_ingreso'=>'2026-09-25','estado'=>'ACTIVO'];
    [$data,$errors]=validate_personal($input); check(!$errors,'Registro válido');
    personal_save($db,$data,null); $id=(int)$db->lastInsertId(); check(personal_find($db,$id)['nombres']==='María','Crear y leer');
    try {personal_save($db,$data,null);throw new RuntimeException('Se aceptó un duplicado');}catch(DomainException $e){echo "OK: Código duplicado rechazado\n";}
    $data['cargo']='Terapeuta'; personal_save($db,$data,$id);check(personal_find($db,$id)['cargo']==='Terapeuta','Editar');
    check(count(personal_list($db,$data['codigo_personal'],'ACTIVO'))===1,'Buscar y filtrar');
    $db->prepare("INSERT INTO actividades (id_personal_responsable,nombre,tipo,fecha,hora_inicio,hora_fin,lugar,cupo) VALUES (?,'Prueba','Prueba','2026-09-25','09:00','10:00','Sala',1)")->execute([$id]);
    $aid=(int)$db->lastInsertId();
    $db->prepare("INSERT INTO incidentes (id_residente,id_tipo_incidente,id_personal_reporta,fecha_hora,lugar,descripcion) VALUES (1,1,?,'2026-09-25 10:00','Sala','Prueba')")->execute([$id]);
    $iid=(int)$db->lastInsertId();
    $impact=personal_impact($db,$id);check((int)$impact['actividades']===1 && (int)$impact['programadas']===1 && (int)$impact['incidentes']===1,'Resumen de impacto de la baja');
    personal_inactivate($db,$id);check(personal_find($db,$id)['estado']==='INACTIVO','Baja lógica');
    $q=$db->prepare('SELECT id_personal_responsable FROM actividades WHERE id_actividad=?');$q->execute([$aid]);check((int)$q->fetchColumn()===$id,'Actividad conserva responsable');
    $q=$db->prepare('SELECT id_personal_reporta FROM incidentes WHERE id_incidente=?');$q->execute([$iid]);check((int)$q->fetchColumn()===$id,'Incidente conserva responsable');
    check(count(personal_list($db,$data['codigo_personal'],'ACTIVO'))===0,'Inactivo excluido del filtro activo');
    foreach(['fecha_ingreso'=>'2026-02-30','email'=>'incorrecto','estado'=>'BORRADO','telefono'=>'abc','nombres'=>'','codigo_personal'=>str_repeat('a',21)] as $field=>$bad) { $test=$input;$test[$field]=$bad;check(count(validate_personal($test)[1])>0,'Validación de '.$field); }
    check(count(personal_list($db,"' OR 1=1 --",''))===0,'Búsqueda parametrizada');
} finally {$db->rollBack();}
echo "Pruebas completadas; cambios revertidos.\n";
