import { api, cargarTabla, mostrarMensaje, solicitar } from "./listado.js";

const formulario = document.querySelector("#crud");
const titulo = document.querySelector("#titulo-formulario");
const guardar = formulario.querySelector('button[type="submit"]');
const campos = formulario.elements;
let id = new URLSearchParams(location.search).get("id");

function modoNuevo() {
  id = null;
  history.replaceState(null, "", "/crud.html");
  formulario.reset();
  titulo.textContent = "Nuevo registro";
  guardar.disabled = false;
}

function cargarResumen() {
  return cargarTabla(api, (eliminado) => {
    if (String(eliminado) === id) modoNuevo();
    return cargarResumen();
  });
}

async function cargarFormulario() {
  if (id === null) {
    guardar.disabled = false;
    return;
  }

  titulo.textContent = "Editar habitación";
  if (!/^[1-9]\d*$/.test(id)) {
    mostrarMensaje("El ID de la habitación debe ser un entero positivo.");
    return;
  }
  try {
    const habitacion = await solicitar(api + "/" + id);
    for (const nombre of ["numero", "piso", "capacidad", "estado", "observaciones"]) {
      campos[nombre].value = habitacion[nombre] ?? "";
    }
    guardar.disabled = false;
  } catch (error) {
    mostrarMensaje(error.message);
  }
}

formulario.addEventListener("submit", async (evento) => {
  evento.preventDefault();
  if (guardar.disabled) return;

  const numero = campos.numero.value.trim();
  const piso = campos.piso.valueAsNumber;
  const capacidad = campos.capacidad.valueAsNumber;
  if (!numero || numero.length > 10) {
    mostrarMensaje("El número es obligatorio y admite hasta 10 caracteres.");
    campos.numero.focus();
    return;
  }
  if (!Number.isSafeInteger(piso) || piso < 1 || !Number.isSafeInteger(capacidad) || capacidad < 1) {
    mostrarMensaje("El piso y la capacidad deben ser enteros positivos.");
    return;
  }

  const datos = {
    numero,
    piso,
    capacidad,
    estado: campos.estado.value,
    observaciones: campos.observaciones.value.trim(),
  };
  guardar.disabled = true;
  try {
    await solicitar(id === null ? api : api + "/" + id, {
      method: id === null ? "POST" : "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(datos),
    });
    mostrarMensaje(id === null ? "Habitación registrada." : "Habitación actualizada.", "success");
    if (id === null) formulario.reset();
    await cargarResumen();
  } catch (error) {
    mostrarMensaje(error.message);
  } finally {
    guardar.disabled = false;
  }
});

cargarFormulario();
cargarResumen();
