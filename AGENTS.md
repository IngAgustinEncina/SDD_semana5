# AGENTS.md — App de Comentarios

## Propósito

App de Comentarios es un sistema cliente-servidor simple para publicar y listar comentarios
en una conversación única. Un lector anónimo escribe un comentario y puede responderlo.
No hay cuentas, ni artículos, ni moderación.

## Reglas que no se negocian

- Validá la longitud (entre 3 y 500 caracteres, contados después de descartar los espacios sobrantes) y el texto no vacío **en el servidor**: el frontend puede avisar antes, pero nunca es la única barrera. (origen: RN-001, RN-002 / D-02)
- En `api/`, usá consultas parametrizadas para todo acceso a datos; nunca concatenes texto recibido del lector en una consulta. (origen: RN-010 / D-06)
- Guardá el texto crudo y neutralizá al mostrar: no escapés ni horneés el texto al guardarlo, para que un intento de incrustar código se vea como texto y no se ejecute. (origen: RN-010 / D-06)
- La fecha la asigna el sistema en el momento de aceptar la publicación; el lector nunca la informa. (origen: RN-003 / D-05)

## Arquitectura

- Ningún componente de negocio habla con la base de datos directamente: todo acceso pasa por la capa de acceso a datos, y la conexión se resuelve en un único lugar. (origen: RN-010 / design.md §3, fronteras entre módulos)
- Un solo nivel de respuestas: las respuestas cuelgan de un comentario y no se puede responder a una respuesta. (origen: RN-004 / D-04)
- No persistas campos de orden: el orden se deriva en cada lectura. (origen: RN-007 / D-03)
- El orden es comentarios del más reciente al más antiguo y respuestas de la más antigua a la más reciente; si dos publicaciones comparten fecha, el desempate es por identificador descendente en comentarios y ascendente en respuestas. (origen: RN-007 / D-03)

## Qué no hacer

- **No agregues** funcionalidad que no tenga un requisito en `requirements.md`; si algo no está en la spec, no lo implementes: preguntame primero. (origen: RN-005, RN-009 / requirements.md §6, fuera de alcance)
- No propongas artículos, cuentas de usuario, autenticación, moderación, ni edición o borrado por parte del autor: el autor es una etiqueta declarada por el lector y no existe el concepto de "comentario propio". (origen: RN-005, RN-009 / D-01, D-05)

## Cómo verificar

Antes de dar por terminada una tarea, confirmá que respetaste las reglas de arriba y
citá cuál aplicaste en cada paso.

Si una decisión no está cubierta por `requirements.md` ni por `design.md`, no la tomes por
tu cuenta: planteá la duda y esperá la respuesta.

`requirements.md` y `design.md`, en `specs/001-publicar-comentarios/`, son la fuente de
verdad. Este archivo resume reglas de esos dos documentos: ante un conflicto, mandan ellos.