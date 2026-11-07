/* Módulo Personal: componentes originales de AdminLTE 4 y fetch a Flask. */
'use strict';
const $ = (selector) => document.querySelector(selector);
const fmt = new Intl.NumberFormat('es-PE', {maximumFractionDigits: 1});
let tabla, grafica, graficaCargos;


/**
 * MENSAJES EN LA INTERFAZ
 * Muestra éxito o error en la alerta compartida. textContent evita interpretar los datos como HTML.
 */
function mensaje(text, type = 'danger') {
  const box = $('#message');
  box.className = `alert alert-${type}`;
  box.textContent = text;
}
function limpiarMensaje() { $('#message').className = 'alert d-none'; }

/**
 * COMUNICACIÓN CON FLASK
 * Centraliza fetch, encabezado JSON, tiempo máximo y manejo de errores. DELETE 204 no tiene cuerpo.
 */
async function api(path, options = {}) {
  const {timeout = 20000, ...init} = options;
  // AbortController funciona también en navegadores que no tienen AbortSignal.timeout.
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeout);
  try {
    const response = await fetch('/api/personal' + path, {
      ...init, headers: {'Content-Type': 'application/json', ...init.headers},
      signal: controller.signal
    });
    if (response.status === 204) return null;
    if (!response.headers.get('content-type')?.includes('application/json')) {
      throw new Error('La dirección no está respondiendo como la API de Flask. Abre el proyecto con iniciar.bat (Windows) o iniciar.sh (Linux).');
    }
    const body = await response.json();
    if (!response.ok || !body.ok) { const error = new Error(body.error || 'No se pudo completar la operación.'); error.data = body.data; throw error; }
    return body;
  } catch (error) {
    if (error.name === 'AbortError') throw new Error('La operación tardó demasiado. Revisa los servicios y vuelve a intentarlo.');
    if (error instanceof TypeError) throw new Error('No se pudo conectar con Flask. Inicia la aplicación y mantén abierta su terminal.');
    throw error;
  } finally {
    clearTimeout(timer);
  }
}
function errorTexto(error) {
  return error.name === 'TimeoutError' ? 'La operación tardó demasiado. Revisa los servicios y vuelve a intentarlo.' : error.message;
}

/**
 * PARÁMETROS DE LOS FILTROS
 * Convierte los campos no vacíos del formulario en query string para que Flask filtre en SQL.
 */
function parametros(form) {
  return new URLSearchParams([...new FormData(form)].filter(([,v]) => v.trim() !== '')).toString();
}

/**
 * VALIDACIÓN PREVIA DE FECHAS
 * Comprueba el orden del rango antes de llamar a la API. El backend vuelve a validarlo.
 */
function validarRango(form) {
  const from = form.elements.fecha_desde.value, to = form.elements.fecha_hasta.value;
  if (from && to && from > to) { mensaje('La fecha desde no puede superar la fecha hasta.'); return false; }
  return true;
}

/**
 * CONFIRMACIÓN CON MODAL BOOTSTRAP
 * Espera Confirmar o Cancelar. Se reutiliza al eliminar personal y al copiar datos a SQL Server.
 */
function confirmar(texto, title, label = 'Confirmar', danger = false) {
  return new Promise(resolve => {
    const el = $('#confirm-modal'), yes = $('#confirm-accept');
    $('#confirm-text').textContent = texto;
    $('#confirm-title').textContent = title;
    yes.textContent = label;
    yes.className = `btn btn-${danger ? 'danger' : 'primary'}`;
    // Evita confirmar mientras Bootstrap todavía está abriendo el modal.
    yes.disabled = true;
    el.addEventListener('shown.bs.modal', () => { yes.disabled = false; }, {once:true});
    const modal = bootstrap.Modal.getOrCreateInstance(el);
    let accepted = false;
    const accept = () => { accepted = true; modal.hide(); };
    yes.addEventListener('click', accept);
    el.addEventListener('hidden.bs.modal', () => {
      yes.removeEventListener('click', accept);
      resolve(accepted);
    }, {once:true});
    modal.show();
  });
}

/**
 * LISTAS DESPLEGABLES
 * Crea opciones con los valores recibidos de la base y conserva la selección si todavía existe.
 */
function llenarOpciones(el, values, all) {
  const previous = el.value;
  el.replaceChildren();
  if (all) el.add(new Option(all, ''));
  values.forEach(value => {
    const opt = document.createElement('option');
    opt.value = value; opt.textContent = value; el.appendChild(opt);
  });
  if (values.includes(previous)) el.value = previous;
}

/**
 * CATÁLOGOS DE LA BASE
 * Carga cargos y especialidades para los filtros y las sugerencias del formulario.
 */
async function catalogos() {
  const {data} = await api('/catalogos');
  if ($('#f-cargo')) llenarOpciones($('#f-cargo'), data.cargos, 'Todos');
  if ($('#f-especialidad')) llenarOpciones($('#f-especialidad'), data.especialidades, 'Todas');
  if ($('#cargos-list')) llenarOpciones($('#cargos-list'), data.cargos);
  if ($('#especialidades-list')) llenarOpciones($('#especialidades-list'), data.especialidades);
}

/**
 * RESUMEN DE LA TRANSFERENCIA
 * Muestra cantidad de filas en MySQL, en SQL Server y fecha del último proceso.
 */
async function resumen() {
  try {
    const {data} = await api('/transferencia/resumen');
    $('#resumen-mysql').textContent = data.mysql;
    $('#resumen-sqlserver').textContent = data.sqlserver;
    // SQL Server almacena hora del servidor; se etiqueta para no confundirla con Lima.
    $('#ultima-transferencia').textContent = data.ultima_transferencia
      ? 'Última transferencia (hora del servidor SQL): ' + data.ultima_transferencia.replace('T',' ')
      : 'Todavía no hay transferencias.';
  } catch (error) {
    try { const origen = await api(''); $('#resumen-mysql').textContent = origen.total; }
    catch { $('#resumen-mysql').textContent = 'Sin conexión'; }
    $('#resumen-sqlserver').textContent = 'Sin conexión';
    $('#ultima-transferencia').textContent = errorTexto(error);
  }
}

/**
 * DASHBOARD E INDICADORES
 * Solicita los KPIs y las series ya calculadas en SQL. Actualiza las seis tarjetas,
 * la gráfica de barras, la distribución circular y la tabla por cargo.
 * También conecta los filtros y el botón Transferir con sus confirmaciones.
 */
async function dashboard() {
  const form = $('#dashboard-filters');
  async function cargar() {
    if (!validarRango(form)) return;
    const button = form.querySelector('[type=submit]'); button.disabled = true;
    limpiarMensaje();
    try {
      const qs = parametros(form);
      const [kpis, series] = await Promise.all([api('/kpis?'+qs),api('/grafico?'+qs)]);
      for (const [key, value] of Object.entries(kpis.data)) {
        const el = $('#kpi-'+key);
        if (el) el.textContent = fmt.format(value);
      }
      const tbody = $('#distribucion-body'); tbody.replaceChildren();
      for (const item of kpis.data.distribucion_cargo) {
        const tr = document.createElement('tr');
        const cargo = document.createElement('td'), cantidad = document.createElement('td');
        cargo.textContent = item.cargo; cantidad.textContent = item.cantidad; cantidad.className='text-end';
        tr.append(cargo,cantidad); tbody.appendChild(tr);
      }
      if (!tbody.children.length) {
        const tr = document.createElement('tr'), td = document.createElement('td');
        td.colSpan=2;td.textContent='Sin personal en el periodo.';tr.appendChild(td);tbody.appendChild(tr);
      }
      if (grafica) grafica.destroy();
      const rows = series.data;
      const textColor = getComputedStyle(document.documentElement).getPropertyValue('--bs-body-color').trim();
      grafica = new Chart($('#carga-chart'), {
        type:'bar', data:{labels: rows.map(r=>r.cargo), datasets:[
          {label:'Actividades a cargo',data:rows.map(r=>r.actividades),backgroundColor:'#0d6efd'},
          {label:'Incidentes reportados',data:rows.map(r=>r.incidentes),backgroundColor:'#fd7e14'}]},
        options:{responsive:true,maintainAspectRatio:false,animation:false,
          plugins:{legend:{labels:{color:textColor}}},
          scales:{y:{beginAtZero:true,ticks:{precision:0,color:textColor}},x:{ticks:{color:textColor}}}}
      });
      if (graficaCargos) graficaCargos.destroy();
      const cargos = kpis.data.distribucion_cargo;
      graficaCargos = new Chart($('#cargo-chart'), {
        type:'doughnut',
        data:{labels:cargos.map(r=>r.cargo),datasets:[{
          data:cargos.map(r=>r.cantidad),
          backgroundColor:['#0d6efd','#198754','#ffc107','#dc3545','#0dcaf0','#6f42c1','#fd7e14','#d63384','#6c757d'],
          borderWidth:2, borderColor:getComputedStyle(document.documentElement).getPropertyValue('--bs-body-bg').trim()
        }]},
        options:{responsive:true,maintainAspectRatio:false,animation:false,cutout:'62%',
          plugins:{legend:{position:'bottom',labels:{color:textColor,boxWidth:10,padding:10,font:{size:11}}}}}
      });
      $('#chart-status').textContent = rows.length ? 'Comparación por cargo del personal seleccionado.' : 'Sin datos para el periodo seleccionado.';
    } catch (error) {
      mensaje(errorTexto(error));
      document.querySelectorAll('[id^=kpi-]').forEach(el=>el.textContent='—');
      if (grafica) { grafica.destroy(); grafica=null; }
      if (graficaCargos) { graficaCargos.destroy(); graficaCargos=null; }
      $('#distribucion-body').replaceChildren();
      $('#chart-status').textContent = 'No se pudieron cargar los datos.';
    } finally { button.disabled = false; }
  }
  form.addEventListener('submit', e=>{e.preventDefault();cargar();});
  form.addEventListener('reset', ()=>{setTimeout(cargar,0);});
  $('#refresh-summary').addEventListener('click', resumen);
  $('#transferir').addEventListener('click', async()=>{
    if (!await confirmar('Se copiará toda la tabla personal de MySQL a SQL Server. Los registros existentes se actualizarán. Los filtros del dashboard no limitan esta copia.', 'Transferir personal', 'Transferir')) return;
    const button = $('#transferir'), result = $('#transfer-result');
    button.disabled=true; button.replaceChildren();
    const spin=document.createElement('span'); spin.className='spinner-border spinner-border-sm me-2';
    button.append(spin,document.createTextNode('Transfiriendo…'));
    result.className='mt-3';result.textContent='La transferencia está en curso…';
    try {
      const r = await api('/transferir',{method:'POST',timeout:90000});
      result.className='alert alert-success mt-3 mb-0';
      result.textContent=`Leídos: ${r.leidos} · Insertados: ${r.insertados} · Actualizados: ${r.actualizados} · Duración: ${r.duracion_ms} ms · ${r.transferido_el}`;
    } catch (error) {result.className='alert alert-danger mt-3 mb-0';result.textContent=errorTexto(error);}
    finally { button.disabled=false;button.textContent='Transferir';await resumen(); }
  });
  await Promise.all([cargar(),resumen()]);
}

/**
 * ACCIONES DE CADA FILA
 * Genera el enlace Editar y el botón Eliminar de la tabla. La API valida la operación final.
 */
function acciones(cell) {
  const id=cell.getRow().getData().id_personal;
  const wrap=document.createElement('div');wrap.className='d-flex gap-1';
  const edit=document.createElement('a');edit.href='/crud.html?id='+id;
  edit.className='btn btn-sm btn-outline-primary';edit.textContent='Editar';
  const del=document.createElement('button');del.type='button';del.className='btn btn-sm btn-outline-danger';del.textContent='Eliminar';
  del.addEventListener('click',()=>borrar(id,()=>cargarListado()));wrap.append(edit,del);return wrap;
}

/**
 * ELIMINACIÓN DESDE LA INTERFAZ
 * Pide confirmación y llama a DELETE. Si hay referencias, muestra el mensaje 409 de la API.
 */
async function borrar(id, after) {
  if (!await confirmar('¿Deseas eliminar este trabajador? Si tiene actividades o incidentes asociados, debes cambiar su estado a INACTIVO.', 'Eliminar personal', 'Eliminar', true)) return;
  try {await api('/'+id,{method:'DELETE'}); await after();mensaje('Trabajador eliminado correctamente.','success');}
  catch(error){mensaje(errorTexto(error));}
}

/**
 * ACTUALIZAR LOS REGISTROS VISIBLES
 * Envía los filtros a Flask y reemplaza los datos de Tabulator. No filtra una copia local de las filas.
 */
async function cargarListado() {
  const form=$('#list-filters');if(!validarRango(form))return;
  limpiarMensaje();$('#list-count').textContent='Cargando registros…';
  try {
    const r=await api('?'+parametros(form));
    await tabla.setData(r.data);$('#list-count').textContent=r.total+' registro(s) encontrado(s).';
  } catch(error){await tabla.setData([]);$('#list-count').textContent='No se pudo cargar el listado.';mensaje(errorTexto(error));}
}

/**
 * TABLA ORIGINAL DE ADMINLTE
 * Configura Tabulator con estilo Bootstrap 5, paginación, columnas, acciones y exportación.
 * Los valores de texto se muestran como texto, sin ejecutar HTML recibido desde la base.
 */
async function listado() {
  // Mismo Tabulator con estilo Bootstrap 5 incluido en tables/data.html.
  tabla=new Tabulator('#personal-table',{
    layout:'fitDataStretch',pagination:true,paginationSize:10,paginationSizeSelector:[5,10,15,25],
    placeholder:'Sin registros para los filtros seleccionados.',
    locale:'es',langs:{es:{pagination:{page_size:'Filas',first:'Primera',first_title:'Primera página',last:'Última',last_title:'Última página',prev:'Anterior',prev_title:'Página anterior',next:'Siguiente',next_title:'Página siguiente',all:'Todas'}}},
    printAsHtml:true, columns:[
      {title:'Código',field:'codigo_personal',formatter:'plaintext',frozen:true},
      {title:'Nombres',field:'nombres',formatter:'plaintext'},
      {title:'Apellidos',field:'apellidos',formatter:'plaintext'},
      {title:'Cargo',field:'cargo',formatter:'plaintext'},
      {title:'Especialidad',field:'especialidad',formatter:'plaintext'},
      {title:'Teléfono',field:'telefono',formatter:'plaintext'},
      {title:'Correo',field:'email',formatter:'plaintext'},
      {title:'Ingreso',field:'fecha_ingreso',formatter:'plaintext'},
      {title:'Estado',field:'estado',formatter:cell=>{
        const badge=document.createElement('span'); const value=cell.getValue();
        badge.className='badge text-bg-'+(value==='ACTIVO'?'success':'secondary');badge.textContent=value;return badge;
      }},
      {title:'Acciones',formatter:acciones,headerSort:false,download:false,print:false,frozen:true,minWidth:160}
    ]
  });
  await new Promise(resolve=>tabla.on('tableBuilt',resolve));
  const form=$('#list-filters');
  form.addEventListener('submit',e=>{e.preventDefault();cargarListado();});
  form.addEventListener('reset',()=>setTimeout(cargarListado,0));
  $('#export-csv').addEventListener('click',()=>tabla.download('csv','personal.csv',{bom:true}));
  $('#export-json').addEventListener('click',()=>tabla.download('json','personal.json'));
  $('#print-table').addEventListener('click',()=>tabla.print(false,true));
  try {await catalogos();} catch(error){mensaje(errorTexto(error));}
  await cargarListado();
}

/**
 * FORMULARIO DE MANTENIMIENTO
 * Sin id en la URL registra con POST. Con id consulta el registro y guarda con PUT.
 * Muestra validaciones y permite borrar con confirmación o volver al listado.
 */
async function crud() {
  const form=$('#personal-form');
  $('#save-personal').disabled=true;
  let id=new URLSearchParams(location.search).get('id');
  if(id && !/^[1-9][0-9]*$/.test(id)){mensaje('Identificador no válido.');$('#save-personal').disabled=true;return;}
  // Formar AAAA-MM-DD explícitamente evita formatos diferentes según Windows/Linux.
  const fechaPartes = new Intl.DateTimeFormat('en-US', {timeZone:'America/Lima', year:'numeric', month:'2-digit', day:'2-digit'}).formatToParts(new Date());
  const fechaCampos = Object.fromEntries(fechaPartes.filter(p=>p.type!=='literal').map(p=>[p.type,p.value]));
  $('#fecha_ingreso').max = `${fechaCampos.year}-${fechaCampos.month}-${fechaCampos.day}`;
  try {await catalogos();}catch(error){mensaje(errorTexto(error));}
  if(id) {
    try {
      const {data}=await api('/'+id);
      for(const [key,value] of Object.entries(data)) if(form.elements[key])form.elements[key].value=value??'';
      $('#form-title').textContent='Editar personal · '+data.codigo_personal;
      $('#delete-personal').classList.remove('d-none');
    } catch(error){mensaje(errorTexto(error));$('#save-personal').disabled=true;return;}
  }
  $('#save-personal').disabled=false;
  $('#new-personal').addEventListener('click',()=>{location.href='/crud.html';});
  $('#delete-personal').addEventListener('click',()=>borrar(id,async()=>{location.href='/tables/data.html';}));
  form.addEventListener('submit',async e=>{
    e.preventDefault();limpiarMensaje();const button=$('#save-personal');button.disabled=true;
    try {
      const data=Object.fromEntries(new FormData(form));
      const r=await api(id?'/'+id:'',{method:id?'PUT':'POST',body:JSON.stringify(data)});
      id=String(r.data.id_personal);history.replaceState(null,'','/crud.html?id='+id);
      $('#form-title').textContent='Editar personal · '+r.data.codigo_personal;
      $('#delete-personal').classList.remove('d-none');
      mensaje('Los datos del trabajador se guardaron correctamente.','success');
    }catch(error){mensaje(errorTexto(error));}finally{button.disabled=false;}
  });
}

/**
 * INICIO DE LA PÁGINA
 * Activa el cambio de tema y ejecuta solo el código de la vista abierta: dashboard, listado o CRUD.
 */
document.addEventListener('DOMContentLoaded',async()=>{
  $('#theme-toggle').addEventListener('click',()=>{
    const root=document.documentElement;root.dataset.bsTheme=root.dataset.bsTheme==='dark'?'light':'dark';
    if(graficaCargos){graficaCargos.options.plugins.legend.labels.color=getComputedStyle(root).getPropertyValue('--bs-body-color').trim();graficaCargos.update();}
    if(grafica){const c=getComputedStyle(root).getPropertyValue('--bs-body-color').trim();grafica.options.plugins.legend.labels.color=c;grafica.options.scales.x.ticks.color=c;grafica.options.scales.y.ticks.color=c;grafica.update();}
  });
  try {const page=document.body.dataset.page;if(page==='dashboard')await dashboard();if(page==='listado')await listado();if(page==='crud')await crud();}
  catch(error){mensaje(errorTexto(error));}
});
