let grafico = null;

function periodo() {
  return {
    fecha_desde: document.getElementById('f-desde').value,
    fecha_hasta: document.getElementById('f-hasta').value,
  };
}

async function cargarKpis() {
  const d = (await api('/kpis' + qs(periodo()))).data;
  const set = (id, v) => { document.getElementById(id).textContent = v; };
  set('kpi-total', d.total_incidentes);
  set('kpi-tasa', d.tasa_por_100_residentes.toFixed(1));
  set('kpi-seg', d.pct_requiere_seguimiento.toFixed(1) + ' %');
  set('kpi-cerr', d.pct_cerrados.toFixed(1) + ' %');
  set('kpi-horas', d.horas_cierre_promedio.toFixed(2) + ' h');
  set('kpi-riesgo', d.incidentes_alto_critico);
}

async function cargarGrafico() {
  const d = (await api('/grafico' + qs(periodo()))).data;
  const colores = ['#198754', '#ffc107'];
  const datasets = d.series.map((s, k) => ({
    label: s.nombre, data: s.valores, backgroundColor: colores[k % colores.length],
  }));
  if (grafico) grafico.destroy();
  grafico = new Chart(document.getElementById('grafico'), {
    type: 'bar',
    data: { labels: d.labels, datasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
      plugins: { legend: { position: 'bottom' } },
    },
  });
}

async function cargarResumen() {
  try {
    const r = await api('/transferencia/resumen');
    document.getElementById('res-mysql').textContent = r.mysql_total;
    document.getElementById('res-sqlserver').textContent = r.sqlserver_total;
    document.getElementById('res-ultima').textContent = r.ultima_transferencia || 'Aún no se ha transferido';
  } catch (e) {
    document.getElementById('res-mysql').textContent = '–';
    document.getElementById('res-sqlserver').textContent = '–';
    document.getElementById('res-ultima').textContent = '–';
    document.getElementById('resultado-transferencia').innerHTML =
      `<div class="alert alert-warning mb-0">${esc(e.message)}</div>`;
  }
}

async function cargarTodo() {
  try {
    await Promise.all([cargarKpis(), cargarGrafico()]);
  } catch (e) {
    aviso(e.message, 'danger');
  }
}

async function transferir() {
  const btn = document.getElementById('btn-transferir');
  const cargando = document.getElementById('cargando');
  const salida = document.getElementById('resultado-transferencia');
  btn.disabled = true;
  cargando.classList.remove('d-none');
  salida.innerHTML = '';
  try {
    const r = await api('/transferir', { method: 'POST' });
    salida.innerHTML = `<div class="alert alert-success mb-0">
      Transferencia completada: <strong>${r.leidos}</strong> leídos,
      <strong>${r.insertados}</strong> insertados,
      <strong>${r.actualizados}</strong> actualizados
      en ${r.duracion_ms} ms (${esc(r.transferido_el)}).</div>`;
  } catch (e) {
    salida.innerHTML = `<div class="alert alert-danger mb-0">${esc(e.message)}</div>`;
  } finally {
    btn.disabled = false;
    cargando.classList.add('d-none');
    cargarResumen();
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const modal = new bootstrap.Modal(document.getElementById('modal-confirmar'));
  document.getElementById('form-periodo').addEventListener('submit', e => {
    e.preventDefault();
    cargarTodo();
  });
  document.getElementById('btn-limpiar-periodo').addEventListener('click', () => {
    document.getElementById('f-desde').value = '';
    document.getElementById('f-hasta').value = '';
    cargarTodo();
  });
  document.getElementById('btn-transferir').addEventListener('click', () => modal.show());
  document.getElementById('btn-confirmar').addEventListener('click', () => {
    modal.hide();
    transferir();
  });
  cargarTodo();
  cargarResumen();
});
