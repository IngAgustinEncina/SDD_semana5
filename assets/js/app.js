"use strict";

const RUTA_CONVERSACION = "api/conversacion.php";
const RUTA_COMENTARIOS = "api/comentarios.php";
const RUTA_RESPUESTAS = "api/respuestas.php";
const TEXTO_MINIMO = 3;
const TEXTO_MAXIMO = 500;
const AUTOR_MAXIMO = 60;

const listaConversacion = document.getElementById("lista-conversacion");
const estadoVacio = document.getElementById("estado-vacio");
const estadoError = document.getElementById("estado-error");
const formularioComentario = document.getElementById("form-comentario");
const autorComentario = document.getElementById("autor-comentario");
const textoComentario = document.getElementById("texto-comentario");
const contadorComentario = document.getElementById("contador-comentario");
const estadoPublicacion = document.getElementById("estado-publicacion");

function longitud(texto) {
  return Array.from(texto).length;
}

function validarPublicacion(texto, autor) {
  const textoNormalizado = texto.trim();
  const autorLimpio = autor.trim();
  const longitudTexto = longitud(textoNormalizado);

  if (longitudTexto === 0) {
    return "El texto no puede estar vacío ni contener solo espacios.";
  }

  if (longitudTexto < TEXTO_MINIMO) {
    return `El texto debe tener al menos ${TEXTO_MINIMO} caracteres.`;
  }

  if (longitudTexto > TEXTO_MAXIMO) {
    return `El texto no puede superar los ${TEXTO_MAXIMO} caracteres.`;
  }

  if (autorLimpio === "") {
    return "El autor no puede estar vacío.";
  }

  if (longitud(autorLimpio) > AUTOR_MAXIMO) {
    return `El autor no puede superar los ${AUTOR_MAXIMO} caracteres.`;
  }

  return null;
}

function actualizarContador(textarea, contador) {
  const cantidad = longitud(textarea.value.trim());
  contador.textContent = `${cantidad} / ${TEXTO_MAXIMO}`;
  contador.classList.toggle("contador-excedido", cantidad > TEXTO_MAXIMO);
}

function mostrarEstado(elemento, mensaje) {
  elemento.textContent = mensaje;
  elemento.hidden = mensaje === "";
}

function formatearFecha(valor) {
  const fecha = new Date(valor.replace(" ", "T"));

  if (Number.isNaN(fecha.getTime())) {
    return valor;
  }

  return new Intl.DateTimeFormat("es", {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(fecha);
}

async function solicitar(url, opciones) {
  let respuesta;

  try {
    respuesta = await fetch(url, opciones);
  } catch {
    throw new Error("No se pudo conectar con el servidor. Intentá de nuevo.");
  }

  let datos = {};

  try {
    datos = await respuesta.json();
  } catch {
    datos = {};
  }

  if (!respuesta.ok) {
    throw new Error(datos.error || "No se pudo completar la operación.");
  }

  return datos;
}

function crearElemento(etiqueta, clase, texto) {
  const elemento = document.createElement(etiqueta);

  if (clase) {
    elemento.className = clase;
  }

  if (texto !== undefined) {
    elemento.textContent = texto;
  }

  return elemento;
}

function crearCabeceraPublicacion(publicacion) {
  const cabecera = crearElemento("header", "publicacion-cabecera");
  const autor = crearElemento("p", "publicacion-autor");
  const nombre = crearElemento("strong", "", publicacion.autor);
  const fecha = crearElemento("time", "publicacion-fecha", formatearFecha(publicacion.fecha));

  autor.append("declarado por ", nombre);
  fecha.dateTime = publicacion.fecha.replace(" ", "T");
  cabecera.append(autor, fecha);

  return cabecera;
}

function crearFormularioRespuesta(comentario) {
  const formulario = crearElemento("form", "formulario formulario-respuesta");
  formulario.hidden = true;
  formulario.noValidate = true;

  const campoAutor = crearElemento("div", "campo");
  const idAutor = `respuesta-autor-${comentario.id}`;
  const etiquetaAutor = crearElemento("label", "", "Autor");
  const entradaAutor = crearElemento("input");
  etiquetaAutor.htmlFor = idAutor;
  entradaAutor.id = idAutor;
  entradaAutor.name = "autor";
  entradaAutor.type = "text";
  entradaAutor.maxLength = AUTOR_MAXIMO;
  entradaAutor.autocomplete = "nickname";
  entradaAutor.required = true;
  entradaAutor.value = autorComentario.value.trim();
  campoAutor.append(etiquetaAutor, entradaAutor);

  const campoTexto = crearElemento("div", "campo");
  const idTexto = `respuesta-texto-${comentario.id}`;
  const etiquetaTexto = crearElemento("label", "", "Respuesta");
  const entradaTexto = crearElemento("textarea");
  etiquetaTexto.htmlFor = idTexto;
  entradaTexto.id = idTexto;
  entradaTexto.name = "texto";
  entradaTexto.rows = 3;
  entradaTexto.required = true;
  entradaTexto.setAttribute("aria-describedby", `respuesta-contador-${comentario.id}`);
  campoTexto.append(etiquetaTexto, entradaTexto);

  const contador = crearElemento("p", "contador", `0 / ${TEXTO_MAXIMO}`);
  contador.id = `respuesta-contador-${comentario.id}`;
  campoTexto.append(contador);

  const acciones = crearElemento("div", "acciones-formulario");
  const botonEnviar = crearElemento("button", "boton", "Publicar respuesta");
  const botonCancelar = crearElemento("button", "boton boton-secundario", "Cancelar");
  botonEnviar.type = "submit";
  botonCancelar.type = "button";
  acciones.append(botonEnviar, botonCancelar);

  const estado = crearElemento("p", "estado estado-error");
  estado.setAttribute("role", "alert");
  estado.hidden = true;

  formulario.append(campoAutor, campoTexto, acciones, estado);

  entradaTexto.addEventListener("input", () => actualizarContador(entradaTexto, contador));

  botonCancelar.addEventListener("click", () => {
    formulario.hidden = true;
    mostrarEstado(estado, "");
  });

  formulario.addEventListener("submit", async (evento) => {
    evento.preventDefault();
    mostrarEstado(estado, "");

    const errorValidacion = validarPublicacion(entradaTexto.value, entradaAutor.value);

    if (errorValidacion) {
      mostrarEstado(estado, errorValidacion);
      entradaTexto.focus();
      return;
    }

    botonEnviar.disabled = true;
    botonEnviar.textContent = "Publicando…";

    try {
      await solicitar(RUTA_RESPUESTAS, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          comentario_id: comentario.id,
          texto: entradaTexto.value,
          autor: entradaAutor.value,
        }),
      });

      entradaTexto.value = "";
      actualizarContador(entradaTexto, contador);
      formulario.hidden = true;
      await cargarConversacion();
    } catch (error) {
      mostrarEstado(estado, error.message);
      entradaTexto.focus();
    } finally {
      botonEnviar.disabled = false;
      botonEnviar.textContent = "Publicar respuesta";
    }
  });

  return { formulario, entradaTexto };
}

function crearComentario(comentario) {
  const articulo = crearElemento("article", "publicacion");
  articulo.append(crearCabeceraPublicacion(comentario));
  articulo.append(crearElemento("p", "publicacion-texto", comentario.texto));

  if (Array.isArray(comentario.respuestas) && comentario.respuestas.length > 0) {
    const respuestas = crearElemento("ol", "respuestas");
    respuestas.setAttribute("aria-label", "Respuestas al comentario");

    for (const respuesta of comentario.respuestas) {
      const item = crearElemento("li", "respuesta");
      item.append(crearCabeceraPublicacion(respuesta));
      item.append(crearElemento("p", "publicacion-texto", respuesta.texto));
      respuestas.append(item);
    }

    articulo.append(respuestas);
  }

  const acciones = crearElemento("div", "acciones-publicacion");
  const botonResponder = crearElemento("button", "boton boton-secundario", "Responder");
  botonResponder.type = "button";
  botonResponder.setAttribute("aria-expanded", "false");
  acciones.append(botonResponder);
  articulo.append(acciones);

  const { formulario, entradaTexto } = crearFormularioRespuesta(comentario);
  articulo.append(formulario);

  botonResponder.addEventListener("click", () => {
    const abrir = formulario.hidden;
    formulario.hidden = !abrir;
    botonResponder.setAttribute("aria-expanded", String(abrir));

    if (abrir) {
      entradaTexto.focus();
    }
  });

  return articulo;
}

function renderizarConversacion(comentarios) {
  listaConversacion.replaceChildren();
  estadoError.hidden = true;

  if (!Array.isArray(comentarios) || comentarios.length === 0) {
    estadoVacio.hidden = false;
    return;
  }

  estadoVacio.hidden = true;

  for (const comentario of comentarios) {
    listaConversacion.append(crearComentario(comentario));
  }
}

async function cargarConversacion() {
  mostrarEstado(estadoError, "");

  try {
    const datos = await solicitar(RUTA_CONVERSACION, {
      headers: { Accept: "application/json" },
      cache: "no-store",
    });

    renderizarConversacion(datos.comentarios);
  } catch (error) {
    listaConversacion.replaceChildren();
    estadoVacio.hidden = true;
    mostrarEstado(estadoError, error.message);
  }
}

formularioComentario.addEventListener("submit", async (evento) => {
  evento.preventDefault();
  mostrarEstado(estadoPublicacion, "");

  const errorValidacion = validarPublicacion(textoComentario.value, autorComentario.value);

  if (errorValidacion) {
    mostrarEstado(estadoPublicacion, errorValidacion);
    textoComentario.focus();
    return;
  }

  const boton = formularioComentario.querySelector('button[type="submit"]');
  boton.disabled = true;
  boton.textContent = "Publicando…";

  try {
    await solicitar(RUTA_COMENTARIOS, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        texto: textoComentario.value,
        autor: autorComentario.value,
      }),
    });

    textoComentario.value = "";
    actualizarContador(textoComentario, contadorComentario);
    textoComentario.focus();
    await cargarConversacion();
  } catch (error) {
    mostrarEstado(estadoPublicacion, error.message);
    textoComentario.focus();
  } finally {
    boton.disabled = false;
    boton.textContent = "Publicar comentario";
  }
});

textoComentario.addEventListener("input", () => actualizarContador(textoComentario, contadorComentario));

actualizarContador(textoComentario, contadorComentario);
cargarConversacion();
