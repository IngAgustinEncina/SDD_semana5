# requirements.md — App de Comentarios

- **Nombre del proyecto:** App de Comentarios
- **Feature:** `001-publicar-comentarios`
- **Versión del documento:** 1.0
- **Estado:** APROBADO (spec de referencia)
- **Última actualización:** Semana 4 — SDD I (Spec First) + cierre de alcance de SDD II
- **Artefacto asociado:** [`design.md`](./design.md) — el CÓMO de esta misma feature

> **Este documento es el QUÉ.** Describe comportamiento observable, no implementación.
> Las decisiones técnicas (stack, módulo, validación en servidor) van en `design.md`.
> Cuando los dos documentos discrepan, el `requirements.md` manda sobre **qué** hay que
> construir y el `design.md` sobre **cómo**; ninguno de los dos manda sobre el otro.

---

## 0. Control de versiones del documento

| Versión | Momento | Cambio | Motivo |
|---|---|---|---|
| 0.1-draft | Semana 3 (en clase) | Contexto, glosario, entidades, RN-001..RN-004, F-COM-001, F-COM-002, F-RES-001 | Redacción colaborativa del spec-first |
| 1.0 | Semana 4 | Se cerró RN-005 (autor sin identidad verificada), se fijó el orden del listado y la convención de IDs | Al cerrar el alcance técnico se detectaron ambigüedades del draft |

**Convención de IDs usada en este documento**

- `RN-xxx` — regla de negocio.
- `F-XXX-nnn` — feature. El prefijo es el del área: `COM` comentarios, `RES` respuestas, `MOD` moderación.
- `CA-n` — criterio de aceptación **dentro** de una feature. Los CA son locales a su feature: `F-COM-001/CA-2` no es el mismo criterio que `F-COM-002/CA-2`.

> 📌 El andamiaje que se entrega en la Semana 1 usa otra numeración de features en los
> comentarios del código (llama `F-COM-004` al listado). A partir de este documento, **los IDs
> de esta spec son la fuente de verdad** y el código se ajusta en la Semana 5. Es el primer
> caso concreto de *spec drift* (desvío silencioso entre spec y código) y la razón por la que
> la spec va versionada dentro del repo.

---

## 1. Contexto y objetivos

**App web para publicar comentarios de lectores sobre artículos.** El sistema permite que
cualquier visitante escriba un comentario y responda comentarios ajenos, en una conversación
plana de un solo nivel de anidamiento. Funciona en un entorno local de escritorio (XAMPP:
Apache + PHP + MySQL), a escala de demostración: no hay usuarios registrados, ni moderación,
ni notificaciones.

**Usuarios:** el lector anónimo, que publica y responde.

**Objetivo del producto:** que la conversación se pueda leer de un vistazo y que publicar un
comentario sea inmediato, sin recargar la página.

**Lo que este documento NO hace:** no describe la arquitectura, el stack ni la estructura de
carpetas. Eso vive en `design.md`.

---

## 2. Glosario

- **Comentario:** aporte publicado por un lector, asociado a un artículo. Es la raíz de la
  conversación y no tiene padre.
- **Respuesta:** aporte publicado por un lector y vinculado **a un comentario**. No puede
  vincularse a otra respuesta.
- **Conversación:** el conjunto completo de comentarios con sus respuestas, tal como se ve en
  la pantalla principal.
- **Artículo:** unidad de contenido sobre la que se comenta. En la v1 **no se modela**: existe
  un único artículo implícito (la página misma). Ver RN-005.
- **Autor:** persona que escribe un comentario o una respuesta. En la v1 **no es una entidad**
  del sistema ni una identidad verificable. Ver RN-005.
- **Moderador:** rol con permisos para editar o eliminar contenido. **Fuera de alcance** de la
  v1.
- **Longitud máxima:** 500 caracteres, contados sobre el texto ya recortado de espacios
  sobrantes. Ver RN-001.

---

## 3. Entidades del dominio

### Comentario

| Campo | Tipo | Regla |
|---|---|---|
| `id` | entero | Identificador único, asignado por el sistema, inmutable. |
| `texto` | cadena | 3 a 500 caracteres tras recortar. Obligatorio. |
| `autor` | cadena | Nombre visible que el lector escribe al publicar. Obligatorio. |
| `fechaCreacion` | fecha y hora | Momento de publicación. La asigna el sistema, no el lector. |

### Respuesta

| Campo | Tipo | Regla |
|---|---|---|
| `id` | entero | Identificador único, asignado por el sistema, inmutable. |
| `comentarioPadre` | referencia a Comentario | Comentario al que responde. Obligatorio. |
| `texto` | cadena | 3 a 500 caracteres tras recortar. Obligatorio. |
| `autor` | cadena | Nombre visible. Obligatorio. |
| `fechaCreacion` | fecha y hora | Momento de publicación. La asigna el sistema. |

### Relaciones

- Un **Comentario** tiene cero o más **Respuestas**.
- Una **Respuesta** pertenece a **exactamente un** Comentario.
- Las Respuestas tienen **un solo nivel de profundidad**: no existe la relación respuesta→respuesta.
- Al eliminar un Comentario, sus Respuestas dejan de existir (RN-006).
- **Comentario** y **Respuesta** comparten forma pero no identidad: un comentario no puede ser
  respuesta de otro, y una respuesta no puede tener respuestas.

### Identidad y autorización

Un `autor` es **una etiqueta mostrada, no una identidad verificada**. El sistema no registra
sesiones ni comprueba quién es el lector: puede escribir el nombre que quiera, incluso uno que
no sea el suyo. Ver RN-005 y `design.md` §2 (D-05).

---

## 4. Reglas de negocio (RN-xxx)

- **RN-001 · Longitud del texto.** El texto de un comentario o de una respuesta tiene entre 3 y
  500 caracteres, contados después de descartar los espacios sobrantes de los extremos.
- **RN-002 · Texto no vacío.** No se publica texto vacío ni texto compuesto únicamente por
  espacios. Una publicación rechazada **no** deja registro parcial alguno.
- **RN-003 · Autoría y fecha.** Toda publicación registra el nombre del autor y la fecha de
  creación. La fecha la determina el sistema en el momento de aceptar la publicación; el lector
  no puede informarla.
- **RN-004 · Un solo nivel de respuestas.** Las respuestas cuelgan de un comentario. No se puede
  responder a una respuesta: el camino más profundo es comentario → respuesta.
- **RN-005 · Anonimato sin identidad.** En la v1 el autor es texto libre declarado por el lector.
  No hay registro, ni contraseña, ni verificación de que el autor exista. En consecuencia, **no
  existe el concepto de "comentario propio"**: nadie es el dueño de un comentario. Esto deja
  fuera de alcance la edición y el borrado por parte del autor, y cualquier función de
  moderación que requiera saber quién escribió qué.
- **RN-006 · Borrado en cascada.** Si un comentario deja de existir, sus respuestas también.
  No quedan respuestas huérfanas visibles.
- **RN-007 · Orden estable.** La conversación se muestra con los comentarios **del más reciente
  al más antiguo**, y las respuestas de cada comentario **de la más antigua a la más reciente**.
  Cuando dos publicaciones comparten fecha, el desempate es por identificador descendente en
  los comentarios y ascendente en las respuestas, de modo que el orden nunca cambia entre dos
  lecturas de la misma conversación.
- **RN-008 · Publicación sin recarga.** Publicar un comentario o una respuesta no recarga la
  página: la conversación se actualiza y el texto publicado queda visible sin que el lector
  vuelva a enviarlo.
- **RN-009 · Un solo artículo.** La v1 no modela artículos: toda la conversación corresponde a
  la página única de la aplicación.
- **RN-010 · Integridad del texto.** El texto publicado se muestra tal como fue escrito, con sus
  saltos de línea. El sistema nunca interpreta el texto del lector como contenido ejecutable o
  como formato: un intento de incrustar código se muestra como texto visible, no se ejecuta.

---

## 5. Features (F-XXX-nnn)

### F-COM-001 — Publicar un comentario

**Historia de usuario:** Como lector, quiero escribir un comentario en el artículo para
compartir mi opinión con quienes lo leen.

**Actor:** lector anónimo. **Precondición:** hay una página de conversación disponible.

**Criterios de aceptación:**

- **CA-1.** Dado un autor y un texto de entre 3 y 500 caracteres, cuando el lector confirma la
  publicación, entonces el comentario queda publicado, aparece en la conversación y muestra su
  autor y su fecha de creación.
- **CA-2.** Dado un texto de más de 500 caracteres o de menos de 3, cuando el lector confirma la
  publicación, entonces el sistema informa el motivo del rechazo, no se publica nada y el texto
  que el lector escribió se conserva en el formulario para que lo corrija.
- **CA-3.** Dado un texto vacío o compuesto solo por espacios, cuando el lector confirma la
  publicación, entonces el sistema informa el rechazo y no se publica nada.
- **CA-4.** Dado un autor vacío o de más de 60 caracteres, cuando el lector confirma la
  publicación, entonces el sistema informa el rechazo y no se publica nada.
- **CA-5.** Dado que el servidor no está disponible, cuando el lector confirma la publicación,
  entonces el sistema informa que no se pudo publicar, no se publica nada y el texto se conserva
  en el formulario.
- **CA-6.** Dado que la publicación fue aceptada, cuando el lector mira la conversación, entonces
  el comentario nuevo aparece primero y la página no se recargó.
- **CA-7.** Dado un texto que incluye etiquetas o caracteres que parecen código, cuando se
  publica, entonces se muestra como texto literal y no ejecuta ni renderiza nada distinto de lo
  escrito.

**Fuera de alcance de esta feature:** edición y borrado del comentario (ver §6, F-MOD-001);
likes, reacciones y adjuntos.

---

### F-COM-002 — Ver la conversación

**Historia de usuario:** Como lector, quiero ver los comentarios y sus respuestas ordenados, para
entender de qué se está discutiendo sin tener que leerlos todos.

**Actor:** lector anónimo. **Precondición:** puede no haber ningún comentario todavía.

**Criterios de aceptación:**

- **CA-1.** Dado que existen comentarios, cuando el lector abre la página, entonces los
  comentarios se muestran del más reciente al más antiguo, y cada uno muestra su texto, su autor,
  su fecha y sus respuestas en orden de más antigua a más reciente.
- **CA-2.** Dado que un comentario tiene cero respuestas, cuando se muestra, entonces no aparece
  ningún indicador de respuestas ni un espacio vacío reservado para ellas.
- **CA-3.** Dado que no existe ningún comentario, cuando el lector abre la página, entonces se
  muestra un mensaje que lo invita a ser el primero, y no una lista vacía.
- **CA-4.** Dado que no se puede obtener la conversación, cuando el lector abre la página, entonces
  se muestra un mensaje de error en el lugar de la lista y el formulario de publicación sigue
  disponible.
- **CA-5.** Dado que la conversación se leyó dos veces sin que nadie publique nada, cuando el
  lector compara ambas lecturas, entonces el orden de los comentarios y de las respuestas es
  idéntico.

**Fuera de alcance de esta feature:** paginación, carga progresiva, filtros por autor o por
fecha, búsqueda y ordenamiento elegido por el usuario.

---

### F-RES-001 — Responder a un comentario

**Historia de usuario:** Como lector, quiero responder a un comentario para contrastar o
sumar a lo que dijo otra persona.

**Actor:** lector anónimo. **Precondición:** existe al menos un comentario visible.

**Criterios de aceptación:**

- **CA-1.** Dado un comentario padre y un texto de entre 3 y 500 caracteres, cuando el lector
  confirma la respuesta, entonces la respuesta queda publicada, se muestra debajo de su
  comentario padre y la página no se recargó.
- **CA-2.** Dado un texto vacío, de menos de 3 o de más de 500 caracteres, cuando el lector
  confirma la respuesta, entonces el sistema informa el rechazo, no se publica nada y el texto se
  conserva para corregirlo.
- **CA-3.** Dado un comentario que ya tiene respuestas, cuando el lector abre el formulario de
  respuesta, entonces las respuestas anteriores siguen visibles y el formulario queda disponible
  para escribir una nueva respuesta al mismo comentario.
- **CA-4.** Dado un intento de responder a una respuesta, cuando se lo somete al sistema, entonces
  se rechaza, no se publica nada y se informa que la conversación admite un solo nivel.
- **CA-5.** Dado un autor inválido, cuando el lector confirma la respuesta, entonces se rechaza
  con el mismo criterio que en la publicación de un comentario.

**Fuera de alcance de esta feature:** respuestas a respuestas (RN-004); mención de otros lectores
con `@`; respuestas al mismo comentario desde el mismo lector.

---

## 6. Alcance del proyecto

### Dentro del alcance (v1)

| ID | Feature | Reglas que la gobiernan |
|---|---|---|
| F-COM-001 | Publicar un comentario | RN-001, RN-002, RN-003, RN-008, RN-010 |
| F-COM-002 | Ver la conversación | RN-007, RN-009 |
| F-RES-001 | Responder a un comentario | RN-001, RN-003, RN-004, RN-008 |

### Fuera del alcance (versiones posteriores)

| ID tentativo | Feature diferida | Por qué queda fuera |
|---|---|---|
| F-MOD-001 | Editar o eliminar un comentario | RN-005 elimina la noción de "comentario propio"; sin identidad verificada no hay forma de comprobar la autoría |
| F-MOD-002 | Moderación (ocultar, marcar) | Requiere el rol de moderador y una vía para conferirlo |
| F-AUT-001 | Registro e inicio de sesión | Agrega un subsistema entero; la v1 asume lector anónimo (RN-005) |
| F-NTF-001 | Avisos de respuestas nuevas | Requiere correo o notificaciones push, fuera del entorno local |
| F-RIC-001 | Valoraciones (likes) y reacciones | Extiende el modelo de datos y el orden; hoy RN-007 ordena solo por fecha |
| F-ART-001 | Varios artículos y threads separados | RN-009 declara un único artículo; exige un modelo de artículo y una ruta por artículo |
| F-PAG-001 | Paginación del listado | La v1 carga la conversación completa; evaluado en `design.md` §4 |
| F-INT-001 | Traducción de la interfaz | Fuera del objetivo del ejercicio |

---

## 7. Pendientes y trazabilidad

### 7.1 Preguntas que quedaron abiertas para la Semana 4

Estas preguntas se respondieron en `design.md`; se dejan registradas porque son la evidencia de
que el spec se cerró contra la arquitectura y no al revés.

| # | Pregunta abierta | Dónde se resolvió |
|---|---|---|
| 1 | ¿La validación de longitud alcanza en el formulario o tiene que repetirla el servidor? | `design.md` D-02 |
| 2 | ¿El orden se calcula al consultar o se guarda precalculado? | `design.md` D-03 |
| 3 | ¿Las respuestas viven en su propia tabla o como columna de tipo en la misma? | `design.md` D-04 |
| 4 | ¿Cómo se sostiene RN-005 sin autenticación? | `design.md` D-05 |
| 5 | ¿Hay que distinguir artículo? | `design.md` D-01 |

### 7.2 Trazabilidad hacia la implementación

Cada criterio de aceptación es verificable contra un comportamiento observable. Un agente que
implemente desde esta spec debería poder demostrar, feature por feature:

| Feature | Criterios | Comportamiento a demostrar |
|---|---|---|
| F-COM-001 | CA-1..CA-7 | Publicar, con sus cuatro modos de rechazo, y ver el comentario sin recarga |
| F-COM-002 | CA-1..CA-5 | Listado ordenado, estado vacío y estado de error |
| F-RES-001 | CA-1..CA-5 | Responder, rechazo de respuesta a respuesta |

### 7.3 Riesgos conocidos de esta spec

- **El `autor` declarado no es una identidad** (RN-005). Es una decisión consciente de la v1,
  no un olvido: se revisará si el proyecto crece.
- **El andamiaje entregado en la Semana 1 no persiste el autor.** Hay una divergencia entre esta
  spec y el código existente; se resuelve en la Semana 5 y se deja anotada en
  `design.md` §5.
- **Sin paginación, el costo de la conversación crece sin techo** (F-PAG-001 diferido). Con
  cientos de comentarios la lectura completa deja de ser razonable. Evaluado en `design.md` §4.
