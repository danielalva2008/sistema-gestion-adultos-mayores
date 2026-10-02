<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Personal · Residencia</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<main>
<header>
<div>
<p class="eyebrow">SISTEMA DE RESIDENCIA · GRUPO 4</p>
<h1>Directorio de personal</h1>
<p>Gestiona a quienes cuidan y acompañan a nuestros residentes.</p>
</div>
<a class="button" href="?accion=nuevo">+ Registrar personal</a>
</header>
<?php if ($flash): ?>
<p class="notice success" role="status">
<?=h($flash)?>
</p>
<?php endif ?>
<?php if ($errors): ?>
<div class="notice error" role="alert">
<strong>Revisa lo siguiente</strong>
<ul>
<?php foreach($errors as $error): ?>
<li>
<?=h($error)?>
</li>
<?php endforeach ?>
</ul>
</div>
<?php endif ?>
<?php if (!$fatal && in_array($action,['nuevo','editar'],true)): ?>
<section>
<h2>
<?=$action==='nuevo'?'Registrar trabajador':'Editar trabajador'?>
</h2>
<p>Los campos con * son obligatorios.</p>
<form method="post">
<input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>">
<input type="hidden" name="accion" value="guardar">
<input type="hidden" name="id" value="<?=h($id)?>">
<div class="grid">
<?php $labels=['codigo_personal'=>'Código de personal','nombres'=>'Nombres','apellidos'=>'Apellidos','cargo'=>'Cargo','especialidad'=>'Especialidad','telefono'=>'Teléfono','email'=>'Correo electrónico','fecha_ingreso'=>'Fecha de ingreso']; foreach($labels as $field=>$label): $required=in_array($field,['codigo_personal','nombres','apellidos','cargo','fecha_ingreso'],true); $type=match($field){'fecha_ingreso'=>'date','email'=>'email','telefono'=>'tel',default=>'text'}; ?>
<label>
<?=h($label)?>
<?=$required?' *':''?>
<input type="<?=$type?>" name="<?=$field?>" maxlength="<?=FIELDS[$field]?>" value="<?=h($data[$field])?>" <?=$required?'required':''?>>
</label>
<?php endforeach ?>
<label>Estado *<select name="estado" required>
<?php foreach(['ACTIVO','INACTIVO'] as $s): ?>
<option value="<?=$s?>" <?=$data['estado']===$s?'selected':''?>>
<?=h($s)?>
</option>
<?php endforeach ?>
</select>
</label>
</div>
<p class="hint">Inactivar conserva las actividades e incidentes asociados. La reasignación de actividades se realiza en su módulo.</p>
<div class="actions">
<button>Guardar datos</button>
<a href="index.php">Cancelar</a>
</div>
</form>
</section>
<?php elseif (!$fatal && $action==='baja'): ?>
<section>
<h2>Inactivar trabajador</h2>
<p>Vas a inactivar a <strong>
<?=h($data['nombres'].' '.$data['apellidos'])?>
</strong> (<?=h($data['codigo_personal'])?>).</p>
<p>Historial asociado: <strong>
<?=h($impact['actividades'])?> actividades</strong> y <strong>
<?=h($impact['incidentes'])?> incidentes</strong>. Se conservarán estas referencias.</p>
<p>Actividades programadas: <strong>
<?=h($impact['programadas'])?>
</strong>. Coordina su reasignación con el responsable de Actividades cuando corresponda.</p>
<form method="post">
<input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>">
<input type="hidden" name="accion" value="inactivar">
<input type="hidden" name="id" value="<?=h($id)?>">
<div class="actions">
<button class="danger">Confirmar inactivación</button>
<a href="index.php">Cancelar</a>
</div>
</form>
</section>
<?php endif ?>
<section>
<h2>Personal registrado</h2>
<form class="filters" method="get">
<label>Buscar por nombre, código, cargo o especialidad<input type="search" name="q" value="<?=h($search)?>" placeholder="Ej. Enfermería">
</label>
<label>Estado<select name="estado">
<option value="">Todos</option>
<?php foreach(['ACTIVO','INACTIVO'] as $s): ?>
<option <?=$state===$s?'selected':''?>>
<?=$s?>
</option>
<?php endforeach ?>
</select>
</label>
<button>Buscar</button>
<a href="index.php">Limpiar</a>
</form>
<?php if (!$fatal): ?>
<p class="hint">
<?=count($rows)?> resultado(s)</p>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Código</th>
<th>Trabajador</th>
<th>Cargo y especialidad</th>
<th>Contacto</th>
<th>Ingreso</th>
<th>Estado</th>
<th>Acciones</th>
</tr>
</thead>
<tbody>
<?php foreach($rows as $row): ?>
<tr>
<td>
<?=h($row['codigo_personal'])?>
</td>
<td>
<strong>
<?=h($row['apellidos'].', '.$row['nombres'])?>
</strong>
</td>
<td>
<?=h($row['cargo'])?>
<small>
<?=h($row['especialidad'])?>
</small>
</td>
<td>
<?=h($row['telefono'])?>
<small>
<?=h($row['email'])?>
</small>
</td>
<td>
<?=h($row['fecha_ingreso'])?>
</td>
<td>
<span class="badge <?=$row['estado']==='ACTIVO'?'active':''?>">
<?=h($row['estado'])?>
</span>
</td>
<td>
<a href="?accion=editar&amp;id=<?=(int)$row['id_personal']?>">Editar</a>
<?php if($row['estado']==='ACTIVO'): ?>
<br>
<a class="deactivate" href="?accion=baja&amp;id=<?=(int)$row['id_personal']?>">Inactivar</a>
<?php endif ?>
</td>
</tr>
<?php endforeach ?>
<?php if(!$rows): ?>
<tr>
<td colspan="7">No hay trabajadores que coincidan con la búsqueda.</td>
</tr>
<?php endif ?>
</tbody>
</table>
</div>
<?php endif ?>
</section>
<footer>Módulo Personal · Datos académicos de prueba</footer>
</main>
</body>
</html>
