const CAMPOS = ['q', 'tipo', 'estado', 'nivel_riesgo', 'seguimiento', 'fecha_desde', 'fecha_hasta'];

function filtros() {
  const f = {};
  CAMPOS.forEach(c => { f[c] = document.getElementById(c).value.trim(); });
  return f;
}

async function cargar() {
  try {
    const r = await api('/' + qs(filtros()));
    document.getElementById('total').textContent = r.total;
    pintarTabla(r.data, i =>
      `<a class="btn btn-sm btn-outline-primary" href="/crud.html?id=${i.id_incidente}"><i class="bi bi-pencil"></i> Editar</a>`);
  } catch (e) {
    aviso(e.message, 'danger');
  }
}

document.addEventListener('DOMContentLoaded', async () => {
  try {
    const cat = await cargarCatalogos();
    llenarSelect(document.getElementById('tipo'), cat.tipos, 'id_tipo_incidente', t => t.nombre);
  } catch (e) {
    aviso(e.message, 'danger');
  }
  document.getElementById('form-filtros').addEventListener('submit', e => {
    e.preventDefault();
    cargar();
  });
  document.getElementById('btn-limpiar').addEventListener('click', () => {
    document.getElementById('form-filtros').reset();
    cargar();
  });
  cargar();
});
