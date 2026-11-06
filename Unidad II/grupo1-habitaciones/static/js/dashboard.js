/* Integrante 5 · Dashboard del módulo Habitaciones
 * Fuente real: GET /api/habitaciones/kpis, GET /grafico,
 * GET /transferencia/resumen, POST /transferir.
 * Modo demostración: datos de referencia visibles y separados de la base real.
 * No se calculan los KPIs aquí: llegan calculados desde MySQL en la API.
 */
(() => {
  'use strict';

  const API = '/api/habitaciones';
  const ENDPOINTS = Object.freeze({
    kpis: `${API}/kpis`,
    grafico: `${API}/grafico`,
    resumen: `${API}/transferencia/resumen`,
    transferir: `${API}/transferir`,
  });

  // Únicamente para la demostración explícita: son los valores del plan del docente.
  const DEMO = Object.freeze({
    kpis: {
      total_habitaciones: 15,
      capacidad_instalada: 24,
      camas_operativas: 23,
      residentes_ocupando: 18,
      tasa_ocupacion: 78.3,
      camas_libres: 5,
      por_estado: { DISPONIBLE: 5, OCUPADA: 9, MANTENIMIENTO: 1 },
    },
    grafico: {
      categorias: ['Piso 1', 'Piso 2', 'Piso 3'],
      series: [
        { nombre: 'Capacidad de camas', datos: [8, 8, 8] },
        { nombre: 'Residentes asignados', datos: [6, 6, 6] },
      ],
    },
    resumen: { mysql_total: 15, sqlserver_total: null, ultima_transferencia: null },
  });

  const colores = { camas: '#157e93', residentes: '#e2a04d', estados: ['#15916d', '#355bb5', '#e2a04d'] };
  const formatoEntero = new Intl.NumberFormat('es-PE', { maximumFractionDigits: 0 });
  const formatoPorcentaje = new Intl.NumberFormat('es-ES', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
  let demostracion = false;
  let confirmando = false;
  let transfiriendo = false;
  let resumenVerificado = false;
  let resultadoIncierto = false;
  let cargaActual = 0;
  let graficoPisos = null;
  let graficoEstados = null;

  const el = (id) => document.getElementById(id);
  const escribir = (id, valor) => { const destino = el(id); if (destino) destino.textContent = String(valor); };
  const mostrar = (id, si) => { const destino = el(id); if (destino) destino.hidden = !si; };
  const numero = (valor) => typeof valor === 'number' && Number.isFinite(valor) ? valor : null;
  const entero = (valor) => numero(valor) === null ? '—' : formatoEntero.format(valor);

  function notificacion(mensaje, tipo = 'info') {
    if (window.Swal && typeof window.Swal.fire === 'function') {
      const icono = { info: 'info', error: 'error', success: 'success', warning: 'warning' }[tipo] || 'info';
      window.Swal.fire({ icon: icono, text: mensaje, confirmButtonColor: '#166c83' });
    } else {
      window.alert(mensaje);
    }
  }

  function estado(nombre, detalle, clase) {
    const etiqueta = el('estado-etiqueta');
    etiqueta.textContent = nombre;
    etiqueta.className = `status-pill status-${clase}`;
    escribir('estado-descripcion', detalle);
  }

  function mensaje(id, texto, tipo = 'error') {
    const destino = el(id);
    if (!destino) return;
    destino.textContent = texto;
    destino.className = `inline-message ${tipo}${id === 'error-kpis' ? ' mb-3' : ''}`;
    destino.hidden = !texto;
  }

  function fechaLegible(valor) {
    if (!valor) return 'Aún no registrada';
    const fecha = new Date(valor);
    return Number.isNaN(fecha.getTime())
      ? String(valor)
      : new Intl.DateTimeFormat('es-PE', { dateStyle: 'medium', timeStyle: 'short' }).format(fecha);
  }

  async function solicitar(url, opciones = {}) {
    const esTransferencia = opciones.method === 'POST';
    const controlador = new AbortController();
    const limite = window.setTimeout(() => controlador.abort(), esTransferencia ? 60000 : 12000);
    try {
      const respuesta = await fetch(url, {
        ...opciones,
        headers: { Accept: 'application/json', ...(opciones.headers || {}) },
        signal: controlador.signal,
        cache: 'no-store',
      });
      const contenido = await respuesta.json().catch(() => null);
      if (!respuesta.ok || !contenido || contenido.ok !== true) {
        const detalle = typeof contenido?.error === 'string' ? contenido.error : `HTTP ${respuesta.status}`;
        const error = new Error(`${detalle} (${respuesta.status})`);
        error.resultadoIncierto = esTransferencia && (!contenido || respuesta.status >= 500);
        throw error;
      }
      return contenido;
    } catch (error) {
      if (error.name === 'AbortError' || error instanceof TypeError) {
        const detalle = error.name === 'AbortError'
          ? 'La solicitud tardó demasiado.' : 'No fue posible conectar con el servidor Flask.';
        const fallo = new Error(detalle);
        fallo.resultadoIncierto = esTransferencia;
        throw fallo;
      }
      throw error;
    } finally {
      window.clearTimeout(limite);
    }
  }

  function dibujarKpis(datos) {
    if (!datos || typeof datos !== 'object') throw new Error('La API de KPIs no devolvió el objeto data.');
    const campos = [
      ['total_habitaciones', 'kpi-total'],
      ['capacidad_instalada', 'kpi-capacidad'],
      ['camas_operativas', 'kpi-operativas'],
      ['camas_libres', 'kpi-libres'],
    ];
    for (const [clave] of campos) {
      if (!Number.isInteger(datos[clave]) || clave !== 'camas_libres' && datos[clave] < 0) {
        throw new Error(`El KPI "${clave}" debe ser un entero JSON válido.`);
      }
    }
    if (numero(datos.tasa_ocupacion) === null || datos.tasa_ocupacion < 0) throw new Error('La tasa de ocupación debe ser un número JSON válido.');
    if (!datos.por_estado || typeof datos.por_estado !== 'object') throw new Error('Falta el objeto "por_estado" de los KPIs.');
    for (const clave of ['DISPONIBLE', 'OCUPADA', 'MANTENIMIENTO']) {
      if (!Number.isInteger(datos.por_estado[clave]) || datos.por_estado[clave] < 0) throw new Error(`El estado ${clave} debe ser un entero JSON válido.`);
    }

    for (const [clave, destino] of campos) escribir(destino, entero(datos[clave]));
    escribir('kpi-ocupacion', `${formatoPorcentaje.format(datos.tasa_ocupacion)} %`);
    const contenedor = el('kpi-estados');
    contenedor.replaceChildren();
    for (const [nombre, etiqueta] of [['DISPONIBLE', 'Disponible'], ['OCUPADA', 'Ocupada'], ['MANTENIMIENTO', 'Mantenimiento']]) {
      const span = document.createElement('span');
      span.textContent = `${etiqueta}: ${entero(datos.por_estado[nombre])}`;
      contenedor.appendChild(span);
    }
    dibujarDistribucion(datos.por_estado, datos.total_habitaciones);
  }

  function sinDatosKpis() {
    for (const id of ['kpi-total', 'kpi-capacidad', 'kpi-operativas', 'kpi-ocupacion', 'kpi-libres']) escribir(id, '—');
    const span = document.createElement('span');
    span.textContent = 'Sin datos';
    el('kpi-estados').replaceChildren(span);
    if (graficoEstados) { graficoEstados.destroy(); graficoEstados = null; }
    el('grafica-estados').replaceChildren();
    escribir('grafica-estados', 'Sin información de estados');
  }

  function seriesValidas(datos) {
    if (!datos || !Array.isArray(datos.categorias) || !Array.isArray(datos.series) || datos.series.length !== 2) return false;
    return datos.categorias.every((categoria) => typeof categoria === 'string')
      && datos.series.every((serie) => serie && typeof serie.nombre === 'string'
        && Array.isArray(serie.datos) && serie.datos.length === datos.categorias.length
        && serie.datos.every((valor) => Number.isInteger(valor) && valor >= 0));
  }

  function dibujarPisos(datos) {
    if (!seriesValidas(datos)) throw new Error('La respuesta de /grafico no incluye categorías y dos series numéricas válidas.');
    const objetivo = el('grafica-pisos');
    if (graficoPisos) { graficoPisos.destroy(); graficoPisos = null; }
    objetivo.replaceChildren();

    if (typeof window.ApexCharts === 'function') {
      graficoPisos = new window.ApexCharts(objetivo, {
        chart: { type: 'bar', height: 310, toolbar: { show: false }, fontFamily: 'inherit' },
        series: datos.series.map((s) => ({ name: s.nombre, data: s.datos })),
        xaxis: { categories: datos.categorias, labels: { style: { fontSize: '13px' } } },
        yaxis: { min: 0, forceNiceScale: true, title: { text: 'Cantidad' }, labels: { formatter: (v) => String(Math.round(v)) } },
        colors: [colores.camas, colores.residentes],
        plotOptions: { bar: { horizontal: false, columnWidth: '44%', borderRadius: 5, dataLabels: { position: 'top' } } },
        dataLabels: { enabled: true, offsetY: -18, style: { colors: ['#40516a'], fontWeight: 600 } },
        grid: { borderColor: '#e8edf3' },
        legend: { position: 'bottom', fontSize: '13px' },
        tooltip: { shared: true, intersect: false },
        noData: { text: 'No hay datos para mostrar' },
      });
      graficoPisos.render();
    } else {
      // Permite visualizar los datos si el CDN de ApexCharts no carga (sin inventar valores).
      const maximo = Math.max(1, ...datos.series.flatMap((s) => s.datos));
      for (let i = 0; i < datos.categorias.length; i += 1) {
        const fila = document.createElement('div');
        fila.className = 'fallback-row';
        const etiqueta = document.createElement('strong');
        etiqueta.textContent = datos.categorias[i];
        const barras = document.createElement('div');
        barras.className = 'fallback-bars';
        datos.series.forEach((serie, indice) => {
          const barra = document.createElement('div');
          barra.className = 'fallback-bar';
          barra.style.width = `${Math.max(9, 100 * serie.datos[i] / maximo)}%`;
          barra.style.backgroundColor = indice === 0 ? colores.camas : colores.residentes;
          barra.textContent = `${serie.datos[i]} ${indice === 0 ? 'camas' : 'residentes'}`;
          barras.appendChild(barra);
        });
        fila.append(etiqueta, barras);
        objetivo.appendChild(fila);
      }
      const leyenda = document.createElement('p');
      leyenda.className = 'helper';
      leyenda.textContent = 'Vista alternativa: la biblioteca de gráficos no está disponible.';
      objetivo.appendChild(leyenda);
    }
  }

  function dibujarDistribucion(estados, totalHabitaciones) {
    const objetivo = el('grafica-estados');
    if (graficoEstados) { graficoEstados.destroy(); graficoEstados = null; }
    objetivo.replaceChildren();
    const valores = ['DISPONIBLE', 'OCUPADA', 'MANTENIMIENTO'].map((k) => estados[k]);
    if (typeof window.ApexCharts === 'function') {
      graficoEstados = new window.ApexCharts(objetivo, {
        chart: { type: 'donut', height: 275, fontFamily: 'inherit' },
        series: valores,
        labels: ['Disponible', 'Ocupada', 'Mantenimiento'],
        colors: colores.estados,
        // El total es el KPI recibido del backend; no se recalcula en JavaScript.
        plotOptions: { pie: { donut: { size: '64%', labels: { show: true, total: { show: true, label: 'Habitaciones', formatter: () => entero(totalHabitaciones) } } } } },
        dataLabels: { enabled: false },
        legend: { position: 'bottom', fontSize: '12px' },
        stroke: { colors: ['#fff'] },
      });
      graficoEstados.render();
    } else {
      const leyenda = document.createElement('div');
      leyenda.className = 'graph-legend justify-content-center pt-5';
      ['Disponible', 'Ocupada', 'Mantenimiento'].forEach((nombre, i) => {
        const item = document.createElement('span');
        const cuadrado = document.createElement('i');
        cuadrado.style.backgroundColor = colores.estados[i];
        item.append(cuadrado, document.createTextNode(`${nombre}: ${entero(valores[i])}`));
        leyenda.appendChild(item);
      });
      objetivo.appendChild(leyenda);
    }
  }

  function dibujarResumen(datos, esDemo = false) {
    if (!datos || typeof datos !== 'object') throw new Error('La API de transferencia no devolvió un resumen válido.');
    if (!Number.isInteger(datos.mysql_total) || datos.mysql_total < 0
      || !(esDemo && datos.sqlserver_total === null)
        && (!Number.isInteger(datos.sqlserver_total) || datos.sqlserver_total < 0)
      || !Object.hasOwn(datos, 'ultima_transferencia')
      || datos.ultima_transferencia !== null && (typeof datos.ultima_transferencia !== 'string'
        || Number.isNaN(Date.parse(datos.ultima_transferencia)))) {
      throw new Error('El resumen debe incluir conteos enteros y una fecha válida o null.');
    }
    escribir('mysql-total', entero(datos.mysql_total));
    escribir('sqlserver-total', entero(datos.sqlserver_total));
    escribir('ultima-transferencia', fechaLegible(datos.ultima_transferencia));
  }

  function borrarResumen() {
    escribir('mysql-total', '—'); escribir('sqlserver-total', '—'); escribir('ultima-transferencia', '—');
  }

  function actualizarControles() {
    const ocupado = confirmando || transfiriendo;
    el('btn-transferir').disabled = !resumenVerificado || demostracion || ocupado || resultadoIncierto;
    for (const id of ['btn-demo', 'btn-real', 'btn-refrescar']) el(id).disabled = ocupado;
    el('card-transferencia').setAttribute('aria-busy', String(transfiriendo));
  }

  function habilitarTransferencia(si) {
    resumenVerificado = si;
    actualizarControles();
    escribir('texto-estado-transferencia', demostracion
      ? 'Deshabilitada: estás viendo datos de demostración.'
      : resultadoIncierto ? 'Resultado sin confirmar. Revisa SQL Server antes de recargar e intentar otra transferencia.'
      : si ? 'Operación real: MySQL → SQL Server, sin duplicados.'
        : 'No se pudo verificar el endpoint; revisa la conexión o el backend.');
  }

  async function cargarDatos() {
    const version = ++cargaActual;
    mostrar('resultado-transferencia', false);
    if (demostracion) {
      dibujarKpis(DEMO.kpis);
      dibujarPisos(DEMO.grafico);
      dibujarResumen(DEMO.resumen, true);
      escribir('fuente-grafico', 'Fuente: valores de demostración del plan del docente; no provienen de la base de datos.');
      for (const id of ['error-kpis', 'error-grafico', 'error-transferencia']) mensaje(id, '');
      habilitarTransferencia(false);
      estado('Demostración', 'Valores de ejemplo del documento del docente; no provienen de MySQL.', 'demo');
      return;
    }

    estado('Conectando…', 'Consultando KPIs, gráfica y resumen de transferencia.', 'loading');
    habilitarTransferencia(false);
    // Al salir de demostración no presentar sus cifras como si fueran datos reales.
    sinDatosKpis();
    borrarResumen();
    if (graficoPisos) { graficoPisos.destroy(); graficoPisos = null; }
    el('grafica-pisos').replaceChildren();
    escribir('fuente-grafico', 'Fuente: API de habitaciones; consulta en curso.');
    const resultados = await Promise.allSettled([
      solicitar(ENDPOINTS.kpis),
      solicitar(ENDPOINTS.grafico),
      solicitar(ENDPOINTS.resumen),
    ]);
    if (demostracion || version !== cargaActual) return; // Ignorar respuestas de una carga anterior.

    const exitos = [];
    const fallos = [];
    const etiquetas = ['Indicadores', 'Gráfica', 'Transferencia'];
    resultados.forEach((resultado, indice) => {
      try {
        if (resultado.status === 'rejected') throw resultado.reason;
        const data = resultado.value;
        if (indice === 0) { dibujarKpis(data.data); mensaje('error-kpis', ''); }
        if (indice === 1) { dibujarPisos(data); mensaje('error-grafico', ''); escribir('fuente-grafico', 'Fuente: API de habitaciones, datos consultados en MySQL.'); }
        if (indice === 2) { dibujarResumen(data); mensaje('error-transferencia', ''); habilitarTransferencia(true); }
        exitos.push(etiquetas[indice]);
      } catch (error) {
        const detalle = String(error.message || error);
        fallos.push(etiquetas[indice]);
        if (indice === 0) { sinDatosKpis(); mensaje('error-kpis', `No se pudieron cargar los KPIs: ${detalle}`); }
        if (indice === 1) { if (graficoPisos) { graficoPisos.destroy(); graficoPisos = null; } el('grafica-pisos').replaceChildren(); mensaje('error-grafico', `No se pudo cargar la gráfica: ${detalle}`); escribir('fuente-grafico', 'No hay datos reales de la gráfica disponibles.'); }
        if (indice === 2) { borrarResumen(); mensaje('error-transferencia', `No se pudo cargar el resumen: ${detalle}`, 'warning'); habilitarTransferencia(false); }
      }
    });
    if (fallos.length === 0) estado('Datos reales', 'Información obtenida correctamente desde las APIs de Flask.', 'live');
    else if (exitos.length > 0) estado('Datos parciales', `Disponibles: ${exitos.join(', ')}. Pendientes: ${fallos.join(', ')}.`, 'warning');
    else estado('APIs pendientes', 'Todavía no hay datos reales. Puedes activar la demostración para revisar el diseño.', 'error');
  }

  async function confirmarTransferencia() {
    if (window.Swal && typeof window.Swal.fire === 'function') {
      const respuesta = await window.Swal.fire({
        title: '¿Transferir habitaciones?',
        text: 'Se sincronizarán las habitaciones de MySQL con SQL Server. Los registros existentes se actualizarán sin duplicarse.',
        icon: 'question', showCancelButton: true, confirmButtonText: 'Sí, transferir', cancelButtonText: 'Cancelar', confirmButtonColor: '#166c83',
      });
      return respuesta.isConfirmed;
    }
    return window.confirm('¿Transferir los registros de habitaciones de MySQL a SQL Server?');
  }

  async function transferir() {
    if (demostracion || confirmando || transfiriendo || !resumenVerificado || resultadoIncierto) return;
    confirmando = true;
    actualizarControles();
    try {
      if (!await confirmarTransferencia()) return;
      // Revalidar después del diálogo; una confirmación nunca omite el bloqueo del modo demo.
      if (demostracion || !resumenVerificado || resultadoIncierto) return;
      transfiriendo = true;
      mostrar('resultado-transferencia', false);
      mensaje('error-transferencia', '');
      actualizarControles();
      mostrar('spinner-transferencia', true);
      escribir('texto-transferir', 'Transfiriendo…');
      escribir('texto-estado-transferencia', 'Enviando datos a SQL Server. Espera el resultado antes de iniciar otra operación.');
      const datos = await solicitar(ENDPOINTS.transferir, { method: 'POST' });
      if (['leidos', 'insertados', 'actualizados'].some((clave) => !Number.isInteger(datos[clave]) || datos[clave] < 0)
        || numero(datos.duracion_ms) === null || datos.duracion_ms < 0
        || typeof datos.transferido_el !== 'string' || Number.isNaN(Date.parse(datos.transferido_el))) {
        const error = new Error('La API no devolvió todos los contadores, la duración y la fecha con el formato esperado.');
        error.resultadoIncierto = true;
        throw error;
      }
      escribir('res-leidos', entero(datos.leidos));
      escribir('res-insertados', entero(datos.insertados));
      escribir('res-actualizados', entero(datos.actualizados));
      escribir('res-duracion', numero(datos.duracion_ms) === null ? 'No indicada' : `${entero(datos.duracion_ms)} ms`);
      escribir('res-fecha', fechaLegible(datos.transferido_el));
      mostrar('resultado-transferencia', true);
      // Actualizar el resumen con datos del backend (no simular el éxito).
      try {
        const respuesta = await solicitar(ENDPOINTS.resumen);
        dibujarResumen(respuesta);
        habilitarTransferencia(true);
      } catch (error) {
        mensaje('error-transferencia', `La transferencia respondió correctamente, pero no se pudo actualizar el resumen: ${error.message}`, 'warning');
        habilitarTransferencia(false);
      }
      notificacion(`Transferencia completada: ${datos.leidos} leídos, ${datos.insertados} insertados y ${datos.actualizados} actualizados.`, 'success');
    } catch (error) {
      resultadoIncierto = Boolean(error.resultadoIncierto);
      const detalle = resultadoIncierto
        ? `No se pudo confirmar el resultado: ${error.message} Revisa SQL Server antes de volver a transferir.`
        : `No se pudo completar la transferencia: ${error.message}`;
      mensaje('error-transferencia', detalle, 'warning');
      notificacion(detalle, 'error');
      habilitarTransferencia(false);
    } finally {
      confirmando = false;
      transfiriendo = false;
      mostrar('spinner-transferencia', false);
      escribir('texto-transferir', 'Transferir a SQL Server');
      habilitarTransferencia(resumenVerificado);
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    el('btn-demo').addEventListener('click', () => {
      if (confirmando || transfiriendo) return;
      demostracion = true;
      mostrar('aviso-demo', true);
      mostrar('btn-demo', false);
      mostrar('btn-real', true);
      cargarDatos();
    });
    el('btn-real').addEventListener('click', () => {
      if (confirmando || transfiriendo) return;
      demostracion = false;
      mostrar('aviso-demo', false);
      mostrar('btn-demo', true);
      mostrar('btn-real', false);
      cargarDatos();
    });
    el('btn-refrescar').addEventListener('click', () => { if (!confirmando && !transfiriendo) cargarDatos(); });
    el('btn-transferir').addEventListener('click', transferir);
    cargarDatos();
  });
})();
