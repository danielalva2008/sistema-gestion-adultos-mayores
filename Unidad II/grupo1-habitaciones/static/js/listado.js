export const api = "/api/habitaciones";
const tbody = document.querySelector("#habitaciones");
let solicitudActual = 0;

export function mostrarMensaje(texto, tipo = "danger") {
  const mensaje = document.querySelector("#mensaje");
  mensaje.className = texto ? "alert alert-" + tipo : "";
  mensaje.textContent = texto;
}

export async function solicitar(url, opciones) {
  const respuesta = await fetch(url, opciones);
  if (respuesta.status === 204) return;

  const resultado = await respuesta.json().catch(() => null);
  if (!respuesta.ok || resultado?.ok !== true) {
    throw new Error(resultado?.error || "La API respondió con HTTP " + respuesta.status + ".");
  }
  return resultado.data;
}

function dibujarTabla(habitaciones, alEliminar) {
  const colores = { DISPONIBLE: "success", OCUPADA: "primary", MANTENIMIENTO: "warning" };
  tbody.replaceChildren();

  if (habitaciones.length === 0) {
    const celda = tbody.insertRow().insertCell();
    celda.colSpan = 8;
    celda.textContent = "No se encontraron habitaciones.";
  }

  for (const habitacion of habitaciones) {
    const fila = tbody.insertRow();
    for (const campo of ["id_habitacion", "numero", "piso", "capacidad", "residentes_asignados"]) {
      fila.insertCell().textContent = habitacion[campo];
    }

    const badge = document.createElement("span");
    badge.className = "badge text-bg-" + (colores[habitacion.estado] || "secondary");
    badge.textContent = habitacion.estado;
    fila.insertCell().append(badge);
    fila.insertCell().textContent = habitacion.observaciones || "—";

    const editar = document.createElement("a");
    editar.className = "btn btn-sm btn-outline-primary me-2";
    editar.href = "/crud.html?id=" + encodeURIComponent(habitacion.id_habitacion);
    editar.textContent = "Editar";

    const eliminar = document.createElement("button");
    eliminar.className = "btn btn-sm btn-outline-danger";
    eliminar.type = "button";
    eliminar.textContent = "Eliminar";
    eliminar.addEventListener("click", async () => {
      if (!window.confirm("¿Eliminar la habitación " + habitacion.numero + "?")) return;
      eliminar.disabled = true;
      try {
        await solicitar(api + "/" + habitacion.id_habitacion, { method: "DELETE" });
        mostrarMensaje("Habitación eliminada.", "success");
        await alEliminar(habitacion.id_habitacion);
      } catch (error) {
        mostrarMensaje(error.message);
      } finally {
        eliminar.disabled = false;
      }
    });
    fila.insertCell().append(editar, eliminar);
  }
}

export async function cargarTabla(url, alEliminar) {
  const solicitud = ++solicitudActual;
  try {
    const habitaciones = await solicitar(url);
    if (solicitud === solicitudActual) dibujarTabla(habitaciones, alEliminar);
  } catch (error) {
    if (solicitud === solicitudActual) {
      tbody.replaceChildren();
      mostrarMensaje("No se pudo cargar el listado. " + error.message);
    }
  }
}

const filtros = document.querySelector("#filtros");
if (filtros) {
  const cargarListado = () => {
    const parametros = new URLSearchParams();
    for (const [nombre, valor] of new FormData(filtros)) {
      if (valor.trim()) parametros.set(nombre, valor.trim());
    }
    const consulta = parametros.size ? "?" + parametros : "";
    return cargarTabla(api + consulta, cargarListado);
  };

  filtros.addEventListener("submit", (evento) => {
    evento.preventDefault();
    mostrarMensaje("");
    cargarListado();
  });
  filtros.addEventListener("reset", () => {
    mostrarMensaje("");
    setTimeout(cargarListado, 0);
  });
  cargarListado();
}
