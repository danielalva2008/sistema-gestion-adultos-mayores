"use strict";
const $ = (s) => document.querySelector(s);
const fmt = (v) => new Intl.NumberFormat('es-PE', {maximumFractionDigits:1}).format(v);
function notice(message, kind='danger') {
  const box=$('#notice'); box.textContent=message; box.className=`alert alert-${kind}`;
}
async function api(path, options={}) {
  const response=await fetch('/api/personal'+path, options);
  if(response.status===204) return null;
  const data=await response.json();
  if(!response.ok || !data.ok) throw new Error(data.error || 'No se pudo completar la solicitud');
  return data;
}
function element(tag, text, cls='') {const el=document.createElement(tag);el.textContent=text;el.className=cls;return el;}
function params(form){return new URLSearchParams([...new FormData(form)].filter(([,v])=>v)).toString();}
async function removeStaff(id, after){
  if(!confirm('¿Eliminar este registro? Solo se permite si no tiene actividades ni incidentes. Esta acción no se puede deshacer.')) return;
  try {await api('/'+id,{method:'DELETE'});notice('Registro eliminado.','success');await after();}catch(e){notice(e.message);}
}
function localDate(){const d=new Date();return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;}
function setupFilters(form, reload){
  form.addEventListener('submit', e=>{e.preventDefault();reload().catch(e=>notice(e.message));});
  form.addEventListener('reset',()=>setTimeout(()=>reload().catch(e=>notice(e.message)),0));
}
async function dashboard(){
  let chart, revision=0;
  const load=async()=>{
    const current=++revision, query='?'+params($('#dashboard-filters'));
    const [k,g]=await Promise.all([api('/kpis'+query),api('/grafico'+query)]);
    if(current!==revision)return;
    $('#notice').classList.add('d-none');
    for(const key of ['personal_activo','antiguedad_promedio','residentes_por_cuidador','actividades_por_responsable','incidentes_por_persona']) $('#'+key).textContent=fmt(k.data[key]);
    $('#distribution').replaceChildren(...k.data.distribucion_cargo.map(row=>{const li=element('li','', 'list-group-item d-flex justify-content-between');li.append(element('span',row.cargo),element('strong',row.total));return li;}));
    $('#total-personal').textContent=`${k.data.total_personal} personas en el periodo seleccionado.`;
    $('#chart-data').replaceChildren(...g.data.map(row=>{const tr=document.createElement('tr');for(const v of [row.cargo,row.actividades,row.incidentes])tr.append(element('td',v));return tr;}));
    if(chart) chart.destroy();
    chart=new Chart($('#workload'),{type:'bar',data:{labels:g.data.map(r=>r.cargo),datasets:[{label:'Actividades',data:g.data.map(r=>r.actividades),backgroundColor:'#397b6e',borderRadius:4},{label:'Incidentes',data:g.data.map(r=>r.incidentes),backgroundColor:'#c9a76c',borderRadius:4}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:22}}},scales:{y:{beginAtZero:true,ticks:{precision:0},grid:{color:'#edf0ee'}},x:{grid:{display:false}}}}});
  };
  async function summary(){
    const {data}=await api('/transferencia/resumen');$('#source-count').textContent=data.mysql;$('#target-count').textContent=data.sqlserver;$('#last-transfer').textContent=data.ultima_transferencia?new Date(data.ultima_transferencia).toLocaleString('es-PE',{timeZone:'America/Lima'})+' (Lima)':'Aún no realizada';
  }
  setupFilters($('#dashboard-filters'),load);
  $('#transfer').addEventListener('click',async()=>{
    if(!confirm('Se transferirán todos los registros de personal a SQL Server. Los existentes se actualizarán. ¿Continuar?'))return;
    const button=$('#transfer');button.disabled=true;button.textContent='Transfiriendo…';$('#transfer-status').textContent='Transferencia en curso…';
    try{const result=await api('/transferir',{method:'POST'});$('#transfer-status').textContent=`Completada: ${result.leidos} leídos, ${result.insertados} insertados y ${result.actualizados} actualizados · ${result.duracion_ms} ms.`;await summary();}
    catch(e){$('#transfer-status').textContent=e.message;}
    finally{button.disabled=false;button.textContent='Transferir datos';}
  });
  await Promise.all([load(),summary().catch(e=>{$('#transfer-status').textContent=e.message;})]);
}
async function listing(){
  const form=$('#list-filters');let revision=0;
  async function load(){
    const current=++revision;const {data,total}=await api('?'+params(form));if(current!==revision)return;
    $('#row-count').textContent=total;
    const rows=data.map(person=>{
      const tr=document.createElement('tr');
      tr.append(element('td',person.codigo_personal));
      const name=element('td','');name.append(element('strong',person.nombres+' '+person.apellidos),element('div',person.email||'Sin correo','text-secondary small'));tr.append(name);
      const role=element('td','');role.append(element('div',person.cargo),element('div',person.especialidad||'—','text-secondary small'));tr.append(role);
      tr.append(element('td',person.fecha_ingreso));
      const state=element('td','');state.append(element('span',person.estado,`badge ${person.estado==='ACTIVO'?'status-active':'status-inactive'}`));tr.append(state);
      const actions=element('td','text');actions.textContent='';const group=element('div','','d-flex gap-2');const link=element('a','Editar','btn btn-sm btn-outline-secondary');link.href='/crud.html?id='+person.id_personal;
      const remove=element('button','Eliminar','btn btn-sm btn-outline-danger');remove.type='button';remove.addEventListener('click',async()=>{remove.disabled=true;await removeStaff(person.id_personal,load);remove.disabled=false;});group.append(link,remove);actions.append(group);tr.append(actions);return tr;
    });
    if(!rows.length){const tr=document.createElement('tr'),td=element('td','No hay registros que coincidan con los filtros.','text-center p-4 text-secondary');td.colSpan=6;tr.append(td);rows.push(tr);}
    $('#staff-rows').replaceChildren(...rows);
  }
  setupFilters(form,load);
  await Promise.all([load(),api('/catalogos').then(({data})=>{for(const [field,key] of [['cargo','cargos'],['especialidad','especialidades']]){for(const value of data[key]){const o=element('option',value);o.value=value;$('#'+field).append(o);}}})]);
}
async function maintenance(){
  const form=$('#staff-form'),id=new URLSearchParams(location.search).get('id');
  if(id&&!/^[1-9][0-9]*$/.test(id)){form.querySelectorAll('input,select,button').forEach(x=>x.disabled=true);throw new Error('Identificador inválido');}
  $('#fecha_ingreso').max=localDate();
  api('/catalogos').then(({data})=>{for(const cargo of data.cargos){const o=document.createElement('option');o.value=cargo;$('#cargo-options').append(o);}}).catch(e=>notice(e.message));
  if(id){
    $('#save').disabled=true;
    const {data}=await api('/'+id);for(const [key,value] of Object.entries(data)){if(form.elements.namedItem(key))form.elements.namedItem(key).value=value??'';}
    $('#form-title').textContent='Editar personal · '+data.codigo_personal;$('#save').textContent='Guardar cambios';$('#save').disabled=false;
    $('#delete-staff').classList.remove('d-none');$('#delete-staff').addEventListener('click',()=>removeStaff(id,()=>{location.href='/tables/data.html';}));
  }
  form.addEventListener('submit',async e=>{
    e.preventDefault();const button=$('#save');button.disabled=true;
    try{const result=await api(id?'/'+id:'',{method:id?'PUT':'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(Object.fromEntries(new FormData(form)))});
      if(!id){location.href='/crud.html?id='+result.data.id_personal+'&created=1';return;}
      notice('Cambios guardados correctamente.','success');
    }catch(e){notice(e.message);}finally{button.disabled=false;}
  });
  if(new URLSearchParams(location.search).get('created'))notice('Personal registrado correctamente.','success');
}
({dashboard,listado:listing,crud:maintenance}[document.body.dataset.page])().catch(e=>notice(e.message));
