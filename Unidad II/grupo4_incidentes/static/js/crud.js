const $ = id => document.getElementById(id);
let idEliminar = null;

function ahoraLocal() {
  const d = new Date();
  d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
  return d.toISOString().slice(0, 16);
}

function actualizarCierre() {
  const cerrado = $('estado').value === 'CERRADO';
  $('fecha_cierre').disabled = !cerrado;
  if (!cerrado) $('fecha_cierre').value = '';
}

function mostrarErrores(lista) {
  $('errores').innerHTML = lista.length
    ? `<div class="alert alert-danger"><ul class="mb-0">${lista.map(m => `<li>${esc(m)}</li>`).join('')}</ul></div>`
    : '';
}

function limpiarFormulario() {
  $('form-incidente').reset();
  $('id_incidente').value = '';
  $('titulo-form').textContent = 'Nuevo incidente';
  mostrarErrores([]);
  actualizarCierre();
  history.replaceState(null, '', '/crud.html');
}

function validarCliente() {
  const errores = [];
  if (!$('id_residente').value) errores.push('Seleccione un residente.');
  if (!$('id_tipo_incidente').value) errores.push('Seleccione el tipo de incidente.');
  if (!$('fecha_hora').value) errores.push('La fecha y hora del incidente es obligatoria.');
  else if ($('fecha_hora').value > ahoraLocal()) errores.push('La fecha del incidente no puede ser futura.');
  if (!$('lugar').value.trim()) errores.push('El lugar es obligatorio.');
  if (!$('descripcion').value.trim()) errores.push('La descripción es obligatoria.');
  if ($('estado').value === 'CERRADO') {
    if (!$('fecha_cierre').value) errores.push('La fecha de cierre es obligatoria cuando el estado es Cerrado.');
    else if ($('fecha_cierre').value <= $('fecha_hora').value) errores.push('La fecha de cierre debe ser posterior a la del incidente.');
  }
  return errores;
}

function datosFormulario() {
  return {
    id_residente: $('id_residente').value,
    id_tipo_incidente: $('id_tipo_incidente').value,
    id_personal_reporta: $('id_personal_reporta').value,
    fecha_hora: $('fecha_hora').value,
    lugar: $('lugar').value,
    descripcion: $('descripcion').value,
    accion_realizada: $('accion_realizada').value,
    requiere_seguimiento: $('requiere_seguimiento').checked,
    estado: $('estado').value,
    fecha_cierre: $('fecha_cierre').value,
  };
}

async function guardar(e) {
  e.preventDefault();
  const errores = validarCliente();
  mostrarErrores(errores);
  if (errores.length) return;
  const id = $('id_incidente').value;
  $('btn-guardar').disabled = true;
  try {
    const r = await api(id ? '/' + id : '/', { method: id ? 'PUT' : 'POST', body: datosFormulario() });
    aviso(id ? `Incidente ${id} actualizado.` : `Incidente ${r.data.id_incidente} creado.`);
    limpiarFormulario();
    cargarTabla();
  } catch (err) {
    mostrarErrores([err.message]);
  } finally {
    $('btn-guardar').disabled = false;
  }
}

async function editar(id) {
  try {
    const d = (await api('/' + id)).data;
    $('id_incidente').value = d.id_incidente;
    $('id_residente').value = d.id_residente;
    $('id_tipo_incidente').value = d.id_tipo_incidente;
    $('id_personal_reporta').value = d.id_personal_reporta || '';
    $('fecha_hora').value = (d.fecha_hora || '').slice(0, 16);
    $('lugar').value = d.lugar;
    $('descripcion').value = d.descripcion;
    $('accion_realizada').value = d.accion_realizada || '';
    $('requiere_seguimiento').checked = !!d.requiere_seguimiento;
    $('estado').value = d.estado;
    actualizarCierre();
    $('fecha_cierre').value = (d.fecha_cierre || '').slice(0, 16);
    $('titulo-form').textContent = `Editar incidente ${d.id_incidente}`;
    mostrarErrores([]);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  } catch (err) {
    aviso(err.message, 'danger');
  }
}

async function cargarTabla() {
  try {
    const r = await api('/');
    pintarTabla(r.data, i => `
      <button class="btn btn-sm btn-outline-primary" onclick="editar(${i.id_incidente})"><i class="bi bi-pencil"></i> Editar</button>
      <button class="btn btn-sm btn-outline-danger" onclick="pedirEliminar(${i.id_incidente})"><i class="bi bi-trash"></i> Eliminar</button>`);
  } catch (err) {
    aviso(err.message, 'danger');
  }
}

let modalEliminar;
function pedirEliminar(id) {
  idEliminar = id;
  $('eliminar-id').textContent = id;
  modalEliminar.show();
}

async function eliminar() {
  modalEliminar.hide();
  try {
    await api('/' + idEliminar, { method: 'DELETE' });
    aviso(`Incidente ${idEliminar} eliminado.`);
    if ($('id_incidente').value == idEliminar) limpiarFormulario();
    cargarTabla();
  } catch (err) {
    aviso(err.message, 'danger');
  }
}

document.addEventListener('DOMContentLoaded', async () => {
  modalEliminar = new bootstrap.Modal($('modal-eliminar'));
  $('fecha_hora').max = ahoraLocal();
  try {
    const cat = await cargarCatalogos();
    llenarSelect($('id_residente'), cat.residentes, 'id_residente', r => r.nombre);
    llenarSelect($('id_tipo_incidente'), cat.tipos, 'id_tipo_incidente', t => `${t.nombre} (${t.nivel_riesgo})`);
    llenarSelect($('id_personal_reporta'), cat.personal, 'id_personal', p => `${p.nombre} – ${p.cargo}`);
  } catch (err) {
    aviso(err.message, 'danger');
  }
  $('estado').addEventListener('change', actualizarCierre);
  $('form-incidente').addEventListener('submit', guardar);
  $('btn-cancelar').addEventListener('click', limpiarFormulario);
  $('btn-eliminar-ok').addEventListener('click', eliminar);
  actualizarCierre();
  cargarTabla();
  const id = new URLSearchParams(location.search).get('id');
  if (id) editar(id);
});
