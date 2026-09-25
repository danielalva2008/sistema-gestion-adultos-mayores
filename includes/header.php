<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($titulo) ?> · Residencia</title><link rel="stylesheet" href="../public/estilos.css"></head><body>
<aside class="sidebar"><a class="brand" href="habitaciones.php"><span class="brand-icon">R</span><span>Residencia<small>GESTIÓN DE HABITACIONES</small></span></a>
<div class="nav-label">ADMINISTRACIÓN</div><a class="nav-item" href="habitaciones.php"><span aria-hidden="true">▦</span> Habitaciones <span class="nav-arrow">›</span></a>
<div class="side-note"><span class="dot"></span> Entorno académico<p>Residencia para adultos mayores</p><small>Desarrollo de Plataformas · Grupo 3</small></div></aside>
<div class="workspace"><header class="topbar"><span>Residencia para adultos mayores</span><span class="group">G3 <b>Grupo 3</b></span></header>
<main><div class="breadcrumb">Administración <span>/</span> Habitaciones<?= $accion !== 'listar' ? ' <span>/</span> '.e($titulo) : '' ?></div>
<?php if ($exito): ?><div class="message success" role="status"><?= e($exito) ?></div><?php endif ?>
<?php if ($error): ?><div class="message error" role="alert"><?= e($error) ?></div><?php endif ?>
