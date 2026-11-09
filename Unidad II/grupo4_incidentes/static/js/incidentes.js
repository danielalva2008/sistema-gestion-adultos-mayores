/* Utilidades compartidas: llamadas a la API, formato y avisos. */
const API = '/api/incidentes';

async function api(ruta, opciones = {}) {
  const cfg = { headers: {}, ...opciones };
  if (cfg.body && typeof cfg.body !== 'string') {
    cfg.body = JSON.stringify(cfg.body);
    cfg.headers['Content-Type'] = 'application/json';
  }
  let resp;
  try {
    resp = await fetch(API + ruta, cfg);
  } catch (e) {
    throw new Error('No se pudo conectar con el servidor');
  }
  if (resp.status === 204) return null;
  let cuerpo = null;
  try { cuerpo = await resp.json(); } catch (e) { /* sin cuerpo JSON */ }
  if (!resp.ok) {
    throw new Error((cuerpo && cuerpo.error) || `Error ${resp.status}`);
  }
  return cuerpo;
}

function qs(params) {
  const p = new URLSearchParams();
  Object.entries(params).forEach(([k, v]) => {
    if (v !== '' && v !== null && v !== undefined) p.append(k, v);
  });
  const s = p.toString();
  return s ? '?' + s : '';
}

function esc(texto) {
  return String(texto ?? '').replace(/[&<>"']/g, c => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function fmtFecha(iso) {
  if (!iso) return '–';
  const [f, h] = iso.split('T');
  const [a, m, d] = f.split('-');
  return `${d}/${m}/${a} ${(h || '').slice(0, 5)}`;
}

function aviso(mensaje, tipo = 'success') {
  const cont = document.getElementById('avisos');
  if (!cont) return;
  cont.innerHTML = `<div class="alert alert-${tipo} alert-dismissible fade show" role="alert">
    ${esc(mensaje)}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button></div>`;
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

const COLOR_ESTADO = { REGISTRADO: 'secondary', EN_SEGUIMIENTO: 'warning', CERRADO: 'success' };
const TEXTO_ESTADO = { REGISTRADO: 'Registrado', EN_SEGUIMIENTO: 'En seguimiento', CERRADO: 'Cerrado' };
const COLOR_RIESGO = { BAJO: 'success', MEDIO: 'info', ALTO: 'warning', CRITICO: 'danger' };

function filaIncidente(i, acciones) {
  return `<tr>
    <td>${i.id_incidente}</td>
    <td>${fmtFecha(i.fecha_hora)}</td>
    <td>${esc(i.residente)}</td>
    <td>${esc(i.tipo)}</td>
    <td><span class="badge text-bg-${COLOR_RIESGO[i.nivel_riesgo] || 'secondary'}">${esc(i.nivel_riesgo)}</span></td>
    <td>${esc(i.lugar)}</td>
    <td><span class="badge text-bg-${COLOR_ESTADO[i.estado] || 'secondary'}">${esc(TEXTO_ESTADO[i.estado] || i.estado)}</span></td>
    <td>${i.requiere_seguimiento ? 'Sí' : 'No'}</td>
    <td class="text-nowrap">${acciones ? acciones(i) : ''}</td>
  </tr>`;
}

function pintarTabla(datos, acciones) {
  const tbody = document.getElementById('tabla');
  tbody.innerHTML = datos.length
    ? datos.map(i => filaIncidente(i, acciones)).join('')
    : '<tr><td colspan="9" class="text-center text-secondary py-4">No hay incidentes que coincidan.</td></tr>';
}

async function cargarCatalogos() {
  return (await api('/catalogos')).data;
}

function llenarSelect(select, items, valor, texto) {
  items.forEach(it => {
    const op = document.createElement('option');
    op.value = it[valor];
    op.textContent = texto(it);
    select.appendChild(op);
  });
}
