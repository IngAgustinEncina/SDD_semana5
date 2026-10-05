# design.md — App de Comentarios

- **Feature:** `001-publicar-comentarios`
- **Versión del documento:** 1.0
- **Estado:** APROBADO (spec técnica de referencia)
- **Módulo del trayecto:** Semana 4 — SDD II (Spec Anchored)
- **requirements.md de referencia:** [`requirements.md`](./requirements.md) (misma carpeta, v1.0)

> **Este documento es el CÓMO.** Registra decisiones de diseño y su porqué.
> **No contiene código de implementación**, ni nombres exactos de funciones, ni la definición
> de tablas. Eso aterriza en la Semana 5.
>
> **Regla de calibración:** si al leer una decisión dejara de tener sentido al cambiar el
> lenguaje de programación, es que estaba describiendo implementación. Este documento debería
> sobrevivir a ese cambio.

---

## 1. Arquitectura general

El sistema es una **aplicación cliente-servidor de dos piezas que se comunican por una interfaz
de red explícita**. De un lado está la **interfaz**, que se ocupa de capturar lo que el lector
escribe, de pedir a la capa de aplicación que persista esa publicación y de pintar el resultado
—incluidas las validaciones que le devuelven el motivo del rechazo—. Del otro lado está la **capa
de aplicación**, que recibe publicaciones y consultas, aplica las reglas de negocio sin
depender de nada de la interfaz, y **persiste** la conversación en un almacén de datos relacional.
La interfaz nunca escribe en el almacén: toda alta o lectura de un comentario pasa por la capa de
aplicación, que es el único lugar donde se deciden las reglas de validación. El diseño es
**sin estado**: no hay sesión de usuario entre una petición y otra, lo que hace que la capa de
aplicación pueda atender publicaciones concurrentes sin coordinar estado compartido
(RN-005). La conversación se modela como **dos agregados relacionados** —comentario y respuesta—
con la restricción de que la respuesta cuelga siempre de un comentario y nunca de otra respuesta
(RN-004); esa restricción no se implementa con lógica condicional dispersa, sino **en el propio
modelo de datos**, de modo que sea imposible guardar una respuesta huérfana o mal anidada
(RN-006). El orden de presentación se deriva en cada lectura a partir del instante de
publicación (RN-007), y la interfaz, una vez que tiene la respuesta de una acción, vuelve a pedir
la conversación completa en lugar de parchear el estado local: la única verdad sobre qué hay
publicado es la que devuelve el servidor.

---

## 2. Decisiones técnicas (ADR)

### D-01 — Un solo artículo en la v1: no se modela la entidad artículo

- **Contexto:** RN-009 declara que toda la conversación corresponde a una página única. Aun así,
  el enunciado original menciona "comentarios sobre artículos", lo que abre la puerta a modelar
  artículos y a colgar cada comentario de uno.
- **Decisión:** la v1 no tiene entidad de artículo. La conversación es un único conjunto, y la
  noción de artículo queda como atributo implícito de la página.
- **Alternativas consideradas:** (a) entidad de artículo presente desde el inicio, con cada
  comentario colgado de un artículo identificable; (b) una columna de artículo
  opcional en el comentario, siempre con el mismo valor.
- **Justificación:** modelar el artículo ahora obligaría a decidir cómo se resuelve "cuál es el
  artículo de esta página" —ruta, parámetro, valor por defecto— sin que ninguna feature lo
  necesite. La alternativa (b) es peor que no tener nada: aparenta generality y agrega una
  columna que nadie consulta y nadie valida. Cuando aparezca F-ART-001 se agrega la entidad
  completa, con su ruta y su índice, no un parche.
- **Requisito relacionado:** RN-009 · F-COM-002/CA-1

### D-02 — La validación es del servidor; el formulario valida solo para dar respuesta inmediata

- **Contexto:** F-COM-001/CA-2 y F-RES-001/CA-2 exigen rechazar textos fuera de rango y vacío.
  El formulario ya tiene un límite de caracteres visible, lo que hace tentador dar la validación
  por resuelta ahí.
- **Decisión:** la regla de longitud y de no-vacío se aplica **siempre en la capa de aplicación**,
  sin excepción. La interfaz puede anticiparla para dar una señal inmediata, pero ese chequeo es
  una cortesía visual y **nunca** la garantía.
- **Alternativas consideradas:** (a) validar solo en la interfaz, descartada porque cualquier
  cliente que hable con la capa de aplicación —un `curl`, una prueba automática, una versión
  futura— la saltearía y el requisito quedaría cumplido solo en apariencia; (b) validar solo en el
  servidor, sin señal inmediata, descartada porque el lector no entendería por qué el botón no
  hace nada.
- **Justificación:** un requisito de aceptación que depende de quién pregunta al sistema no es un
  requisito: es una casualidad de la implementación actual. La validación en el servidor es la
  única versión del criterio que sigue siendo cierta cuando hay un segundo cliente. La señal
  inmediata se conserva como mejora de experiencia, asumida como redundante y no como garantía.
- **Requisito relacionado:** F-COM-001/CA-2, F-COM-001/CA-3, F-RES-001/CA-2 · RN-001, RN-002

### D-03 — El orden se deriva en cada lectura; no se persiste ningún campo de orden

- **Contexto:** RN-007 exige dos órdenes distintos según el nivel —comentarios del más nuevo al
  más viejo, respuestas de la más antigua a la más reciente— y además un orden estable entre dos
  lecturas de la misma conversación.
- **Decisión:** el orden se calcula **en cada consulta**, a partir del instante de publicación, con
  el identificador como desempate. No se guarda ningún campo de posición ni de secuencia en el
  modelo de datos.
- **Alternativas consideradas:** (a) guardar un contador o una posición por registro, descartada
  porque obliga a reindexar cuando una publicación se borra o se inserta en el medio, y cada
  reindexación es una oportunidad de inconsistencia silenciosa; (b) confiar solo en el instante
  de publicación, descartada porque dos publicaciones pueden compartir instante y el resultado
  dependería del plan de ejecución de la base.
- **Justificación:** el orden es una **vista**, no un hecho almacenado. Derivarlo en la lectura
  hace que sea imposible que el orden guardado contradiga a los datos: si cambia un comentario,
  el orden cambia con él, sin migración ni reparación. El desempate por identificador cierra el
  caso de los instantes iguales sin agregar un campo.
- **Requisito relacionado:** F-COM-002/CA-1, F-COM-002/CA-5 · RN-007

### D-04 — Comentario y respuesta son dos entidades con una relación de pertenencia, no una tabla con nivel

- **Contexto:** RN-004 limita la conversación a un nivel y RN-006 exige que al desaparecer un
  comentario desaparezcan sus respuestas. Cabe modelarlo como una sola colección con una columna
  de padre, o como dos colecciones relacionadas.
- **Decisión:** dos colecciones separadas. Cada respuesta **pertenece a** un comentario, y esa
  pertenencia se declara en el modelo de datos con la integridad correspondiente, incluida la
  eliminación en cascada.
- **Alternativas consideradas:** (a) una sola colección con columna de padre y discriminante de
  nivel, descartada porque "no tiene padre" y "tiene padre" pasan a ser el mismo hecho y el
  nivel se vuelve un dato que hay que confiar en lugar de una propiedad del modelo; (b) un árbol
  de nodos genéricos con clave de padre en la propia colección, descartada porque compra una
  flexibilidad —niveles infinitos— que la spec prohíbe explícitamente y que nadie pidió.
- **Justificación:** con dos colecciones, "responder a una respuesta" **no se puede expresar**:
  el modelo no tiene dónde guardarlo, así que la regla se cumple por construcción y no por
  acordarse de comprobarla. Es la diferencia entre una regla que depende de la disciplina de
  quien programa y una que depende de la forma del modelo. La cascada se declara en el mismo
  lugar, y por eso RN-006 tampoco queda en manos de una memorable clause.
- **Requisito relacionado:** F-RES-001/CA-4, F-COM-002/CA-2 · RN-004, RN-006

### D-05 — El autor es una etiqueta declarada por el lector, y el sistema lo asume así de entrada

- **Contexto:** RN-003 obliga a registrar un autor con cada publicación, y a la vez RN-005 prohíbe
  tratar al autor como identidad verificada porque la v1 no tiene registro ni sesión. De ahí sale
  una tensión real: el dato se guarda, pero no identifica a nadie.
- **Decisión:** el autor es **texto libre que el lector escribe y declara**. No hay cuentas, ni
  sesiones, ni verificación de que el nombre exista. El sistema lo muestra siempre como
  "declarado por", y ninguna funcionalidad puede afirmar que un comentario es de alguien.
- **Alternativas consideradas:** (a) pedir el nombre una sola vez y guardarlo en sesión,
  descartada porque introduce estado en el servidor justo donde el diseño sin estado proyecta que
  no lo haya, a cambio de resolver un problema que la v1 no tiene;
  (b) generar un alias anónimo automático, descartada porque esconde que el nombre es del lector y
  hace la conversación menos legible; (c) dejar el autor vacío y tratarlo como "anónimo"
  genérico, descartada porque contradice RN-003 y empobrece la conversación.
- **Justificación:** la consecuencia de esta decisión no es un detalle: **no existe el concepto de
  "comentario propio"**. Eso es lo que expulsa la edición y el borrado por parte del autor del
  alcance (F-MOD-001) sin que quede una regla huérfana, y es lo que obliga a que la moderación sea
  una feature futura completa en lugar de un permiso. Nombrar el modelo "autor declarado" desde
  ahora evita que en tres meses alguien construya encima de la suposición equivocada.
- **Requisito relacionado:** F-COM-001/CA-4, F-COM-001/CA-1 · RN-003, RN-005

### D-06 — El texto publicado se guarda crudo y se neutraliza al mostrarlo

- **Contexto:** RN-010 exige que un intento de incrustar código se muestre como texto y no se
  ejecute, y que el texto se muestre tal como fue escrito.
- **Decisión:** el almacén guarda **el texto exactamente como lo envió el lector**. La
  neutralización ocurre **en el momento de mostrar**, en la interfaz, que inserta el texto como
  contenido literal y nunca como markup. El sistema no reescribe, no limpia ni "sanitiza" el dato
  guardado.
- **Alternativas consideradas:** (a) limpiar el texto al recibirlo, descartada porque reescribe un
  dato que el lector escribió y deja la decisión de qué es peligroso repartida entre dos capas;
  (b) escapar al guardar y guardar el texto ya alterado, descartada por el mismo motivo, con el
  agregado de que luego el dato guardado ya no es el que el lector escribió; (c) permitir un
  subconjunto de formato, descartada porque introduce un mini-lenguaje que la spec no pide y cuyo
  alcance de seguridad es mucho mayor que el de un texto plano.
- **Justificación:** guardar crudo conserva la información — si mañana aparece moderación o un
  cambio de representación, el dato original sigue ahí— y concentra la defensa en el único punto
  donde el texto se convierte en pantalla. La neutralización en la presentación es además la
  decisión que sobrevive a un cambio de framework o de lenguaje, que es justamente la prueba de
  calibración de este documento.
- **Requisito relacionado:** F-COM-001/CA-7, F-COM-002/CA-1 · RN-010

### D-07 — Publicar y volver a leer: la interfaz no mantiene su propia versión de la conversación

- **Contexto:** RN-008 exige que publicar no recargue la página, y el criterio de F-COM-002/CA-4
  que una lectura fallida muestre error sin romper la pantalla.
- **Decisión:** la interfaz **no mantiene un estado propio de la conversación**. Después de una
  acción exitosa pide de nuevo la conversación completa y reemplaza lo que muestra; la lectura
  posterior a la escritura es la que define lo que se ve.
- **Alternativas consideradas:** (a) insertar en la lista el comentario recién creado sin volver a
  preguntar al servidor, descartada porque el identificador y la fecha definitivos los conoce el
  servidor, y porque dos lectores publicando a la vez producirían listas divergentes; (b) ir
  sumando parches incrementales sobre la lista en memoria, descartada por el mismo motivo, con el
  costo adicional de tener que replicar en el cliente la lógica de orden de RN-007.
- **Justificación:** es la opción más simple de mantener correcta y la que **no puede desincronizarse**:
  si lo que se muestra siempre fue lo que el servidor acaba de devolver, no hay estado local que
  pueda quedar viejo. El precio —un viaje más por cada publicación— es aceptable en un entorno
  local y no es el recurso escaso. La alternativa (b) duplicaría la regla de orden en dos lugares,
  que es exactamente la forma de spec drift que este proyecto quiere evitar.
- **Requisito relacionado:** F-COM-001/CA-6, F-RES-001/CA-1, F-COM-002/CA-4 · RN-008

### Matriz de trazabilidad (requisito → decisión)

| Requisito | Decisiones que lo sostienen | ¿Cubierto por alguna decisión? |
|---|---|---|
| RN-001 Longitud del texto | D-02 | Sí |
| RN-002 Texto no vacío | D-02, D-06 | Sí |
| RN-003 Autoría y fecha | D-05, D-03 | Sí |
| RN-004 Un solo nivel | D-04 | Sí |
| RN-005 Anonimato sin identidad | D-05 | Sí |
| RN-006 Borrado en cascada | D-04 | Sí |
| RN-007 Orden estable | D-03 | Sí |
| RN-008 Publicación sin recarga | D-07 | Sí |
| RN-009 Un solo artículo | D-01 | Sí |
| RN-010 Integridad del texto | D-06 | Sí |
| F-COM-001/CA-1 | D-05, D-07 | Sí |
| F-COM-001/CA-2, CA-3 | D-02 | Sí |
| F-COM-001/CA-4 | D-05 | Sí |
| F-COM-001/CA-5 | D-02, D-07 | Sí |
| F-COM-001/CA-6 | D-07 | Sí |
| F-COM-001/CA-7 | D-06 | Sí |
| F-COM-002/CA-1, CA-5 | D-03, D-06, D-01 | Sí |
| F-COM-002/CA-2 | D-04 | Sí |
| F-COM-002/CA-3, CA-4 | D-07 | Sí |
| F-RES-001/CA-1 | D-07 | Sí |
| F-RES-001/CA-2, CA-5 | D-02, D-05 | Sí |
| F-RES-001/CA-3 | D-04 | Sí |
| F-RES-001/CA-4 | D-04 | Sí |

**Lectura de la matriz:** las diez reglas de negocio y los diecisiete criterios de aceptación
tienen al menos una decisión que los sostiene. No hay ninguna decisión en este documento que no
apunte a un requisito: si en la Semana 5 aparece una decisión sin fila aquí, o es implementación
—y entonces no iba en este documento— o es una decisión de diseño que se tomó sin que la spec la
pidiera.

---

## 3. Estructura de módulos

Componentes lógicos, con su responsabilidad. Los nombres de archivo exactos se deciden al
implementar.

- **Interfaz de conversación** — presenta la conversación completa y coordina la actualización
  tras cada acción. No decide reglas de negocio: si algo no se puede hacer, se lo pregunta a la
  capa de aplicación y muestra el motivo que devuelve.
- **Componente de publicación de comentarios** — recibe una solicitud de alta de comentario,
  aplica las reglas de longitud, no-vacío y autor, y la persiste. Es el dueño exclusivo de la
  regla que ninguna publicación se guarda a medias.
- **Componente de publicación de respuestas** — recibe una solicitud de respuesta, comprueba que
  la referencia apunta a un comentario existente y persiste la respuesta colgando de él. Rechaza
  por construcción cualquier anidamiento mayor que uno.
- **Componente de consulta de conversación** — entrega la conversación ya ordenada según RN-007,
  con las respuestas de cada comentario en su orden. No aplica reglas de escritura: solo lee y
  ordena.
- **Componente de validación** — reutilizable por los dos componentes de publicación. Concentra
  las reglas de contenido para que un comentario y una respuesta no puedan divergir en cómo se
  los valida. Si mañana aparece una tercera forma de publicar, hereda las mismas reglas en lugar
  de reimplementarlas.
- **Capa de acceso a datos** — frontera única hacia el almacén. Ningún componente de negocio
  habla con la base de datos directamente, y ningún acceso se construye concatenando texto
  recebido del lector. Todos los accesos usan consultas parametrizadas.
- **Configuración de conexión** — credenciales y ubicación del almacén, en un único lugar
  fuera del resto del código, para que cambiar de entorno no implique tocar lógica.

**Fronteras entre módulos:** la única forma de hablar con la capa de aplicación es la interfaz de
conversación. La capa de aplicación no conoce cómo se ve nada. El acceso a datos no conoce
reglas de negocio, solo cómo leer y guardar. Ninguna de estas fronteras se cruza "por comodidad"
en la Semana 5: cada cruce es una decisión nueva y debería entrar como un ADR nuevo.

---

## 4. Fuera de alcance técnico

Enfoques evaluados y descartados para esta feature, con su motivo.

- **Capa de aplicación como servicio único en lugar de componentes separados** — se descartó
  porque con dos tipos de publicación y una lectura, un punto único de entrada concentra
  validación, escritura y orden en el mismo lugar; separar por responsabilidad cuesta poco hoy y
  evita que una corrección en publicación termine tocando el listado.
- **Servicio de moderación o puntos de extensión para autorización** — se descartó porque RN-005 elimina la
  noción de autoría verificable: no hay nada que autorizar todavía. Reaparece con F-MOD-001 o
  F-AUT-001.
- **Cache del listado de conversación** — se descartó por la complejidad de invalidación: la
  conversación cambia con cada publicación, y un listado cacheado obliga a decidir cuándo se
  invalida. El costo de leer siempre es bajo en un entorno local; la complejidad de acertar la
  invalidación no lo compensa.
- **Paginación del listado** — se descartó porque RN-007 no contempla paginación y con el volumen
  del ejercicio la lectura completa es suficiente. Deuda consciente, no un olvido: si el volumen
  crece, es F-PAG-001.
- **Autenticación, sesiones y usuarios** — se descartó porque está fuera del alcance del
  requirements.md (RN-005, F-AUT-001). Es la decisión más grande que este documento no toma.
- **Marco de trabajo o biblioteca de interfaz** — se descartó para mantener el alcance del
  ejercicio y evitar que la apariencia de la aplicación dependa de dependencias externas.
- **Formato enriquecido en el texto** (negritas, enlaces, imágenes) — se descartó por D-06:
  introducir un mini-lenguaje multiplica el riesgo de D-06 sin que ninguna feature lo pida, y
  choca con RN-010.
- **Despliegue fuera del entorno local** — se descartó porque el requirements.md no define
  requisitos de disponibilidad, y agregar una decisión de despliegue sin requisito que la origine
  es justamente el caso que la trazabilidad busca evitar.

---

## 5. Dudas técnicas pendientes

Lo que no se pudo resolver con la información disponible. No son un defecto del documento: son la
forma honesta de dejar escrito lo que todavía no se sabe.

- **¿Qué pasa si el lector publica dos veces el mismo texto?** F-COM-001/CA-1 no dice nada de
  duplicados: el sistema los mostraría como dos comentarios distintos. ¿Debe detectarse el
  duplicado exacto, o aceptarlo como parte del modelo? No se resolvió; afecta a la validación y
  por tanto a D-02.
- **¿Quién decide que el servidor "no está disponible"?** F-COM-001/CA-5 pide que el lector vea un
  mensaje de error, pero no dice si alcanza con que la publicación falle o si hace falta un aviso
  previo de desconexión. Queda abierto si el error se muestra en el lugar del formulario o
  superpuesto.
- **¿Qué límite tiene la conversación?** Con F-PAG-001 diferido, la lectura completa no tiene tope.
  No se fijó un número de comentarios ni de respuestas por conversación, ni un tiempo máximo de
  respuesta. Con mil comentarios, la lectura se vuelve lenta y la spec no lo cubre.
- **¿Qué pasa con los comentarios que ya existen en el andamiaje de la Semana 1?** Ese código no
  persiste el autor, que RN-003 exige. Hay tres caminos — migrar los datos existentes, aceptar
  que los previos queden sin autor, o regenerar la base — y los tres cambian el alcance. No se
  decidió; se resuelve al implementar.
- **¿El autor declarado debería tener un formato?** RN-003 exige un autor, pero no dice si "a", "A",
  "  " o un nombre de 200 caracteres son válidos. El criterio F-COM-001/CA-4 fija 60 como máximo
  sin que la regla de negocio lo justifique: es un límite inventado en la capa de criterios y
  probablemente deba subir a la spec.
- **¿Hace falta registrar un texto muy antiguo?** RN-003 dice que la fecha la asigna el sistema, pero
  no aclara si se guarda en la zona horaria del servidor o en una referencia única. En un entorno
  local no se nota; al compartir la base, sí.
- **¿La moderación debería poder distinguir un comentario reportado de uno editado?** Si F-MOD-001
  entra algún día, la conversación pasa a tener estados que esta v1 no considera. No se traza un
  diseño por adelantado: sería diseñar contra el requirements.md, que todavía no existe.

---

*Documento vivo: cualquier cambio se propone como modificación versionada de este archivo, con
su motivo, y su efecto sobre la matriz de trazabilidad se revisa en el mismo cambio. Si una
decisión deja de sostenerse, se actualiza su ADR — con su contexto nuevo — en lugar de borrar el
anterior.*
