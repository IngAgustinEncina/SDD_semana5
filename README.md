# App de Comentarios

Aplicación cliente-servidor para publicar comentarios y respuestas sobre una conversación
única. Un lector anónimo escribe un comentario y puede responderlo; no hay cuentas, ni
artículos, ni moderación.

La fuente de verdad del comportamiento es
[`specs/001-publicar-comentarios/requirements.md`](specs/001-publicar-comentarios/requirements.md)
y las decisiones técnicas están en
[`specs/001-publicar-comentarios/design.md`](specs/001-publicar-comentarios/design.md).

## Stack

- PHP 8.2 (sin frameworks)
- MySQL / MariaDB (XAMPP)
- HTML, CSS y JavaScript sin dependencias externas
- JSON sobre HTTP entre la interfaz y la capa de aplicación

## Requisitos

- XAMPP con Apache, PHP 8.2 y MySQL
- Git 2.5 o superior (para el flujo con worktrees)
- El repositorio en `C:\xampp\htdocs\SDD_semana9`

## Puesta en marcha con Apache (XAMPP)

1. Iniciá **Apache** y **MySQL** desde el panel de control de XAMPP.
2. Importá el esquema la primera vez:

   ```powershell
   Get-Content .\database\schema.sql | & 'C:\xampp\mysql\bin\mysql.exe' -u root
   ```

3. Abrí <http://localhost/SDD_semana9/>.

La configuración de conexión está en `config/database.php` y se puede sobrescribir con las
variables de entorno `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` y `DB_PASS`. Los valores por
defecto son `127.0.0.1`, `3306`, `app_comentarios_v1`, `root` y contraseña vacía.

## Puesta en marcha sin Apache

Desde la raíz del proyecto:

```powershell
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8080 -t .
```

Luego abrí <http://127.0.0.1:8080/>. El servidor embebido debe arrancarse desde la raíz para
que las rutas relativas de `api/` y `assets/` resuelvan.

## API

| Método | Ruta | Cuerpo | Respuesta |
|---|---|---|---|
| `GET` | `api/conversacion.php` | — | `{ "comentarios": [...] }` |
| `POST` | `api/comentarios.php` | `{ "texto": "...", "autor": "..." }` | `201` con el comentario creado |
| `POST` | `api/respuestas.php` | `{ "comentario_id": 1, "texto": "...", "autor": "..." }` | `201` con la respuesta creada |

Errores de validación: `422` con `{ "error": "motivo" }`. Método incorrecto: `405`.
Error inesperado: `500` con un mensaje genérico (el detalle queda en el log de PHP).

Ejemplo en PowerShell:

```powershell
$cuerpo = @{ texto = 'Primer comentario'; autor = 'Ana' } | ConvertTo-Json
Invoke-RestMethod -Uri 'http://localhost/SDD_semana9/api/comentarios.php' `
  -Method Post -ContentType 'application/json' -Body $cuerpo
```

## Estructura

```
api/          Puntos de entrada HTTP (JSON)
assets/       CSS y JavaScript de la interfaz
config/       Configuración de conexión
database/     Esquema SQL
docs/         Guías operativas (worktrees)
src/          Dominio, validación y acceso a datos
specs/        requirements.md y design.md
index.html    Pantalla única de la conversación
```

La interfaz nunca accede a MySQL: todo pasa por `api/`, luego por los componentes de `src/` y
finalmente por `AccesoDatos`, que es la única frontera con la base. Las consultas son
parametrizadas y el texto se guarda crudo y se muestra como texto (`textContent`), según
RN-010 / D-06.

## Verificación funcional

| Feature | Qué probar |
|---|---|
| F-COM-001 | Publicar un comentario válido (CA-1); rechazar texto de menos de 3 o más de 500 caracteres (CA-2); rechazar texto vacío o solo espacios (CA-3); rechazar autor vacío o de más de 60 caracteres (CA-4); ver el comentario primero sin recargar (CA-6); publicar `<b>hola</b>` y verlo como texto (CA-7). |
| F-COM-002 | Orden de comentarios del más reciente al más antiguo y respuestas de la más antigua a la más reciente (CA-1); sin indicador cuando no hay respuestas (CA-2); mensaje de estado vacío (CA-3); error de lectura sin romper el formulario (CA-4); dos lecturas seguidas devuelven el mismo orden (CA-5). |
| F-RES-001 | Responder un comentario y ver la respuesta debajo sin recargar (CA-1); rechazos de texto con el mismo criterio que un comentario (CA-2); poder responder de nuevo al mismo comentario (CA-3); enviar `respuesta_id` o `respuesta_padre_id` y recibir el rechazo de un solo nivel (CA-4); autor inválido rechazado (CA-5). |

## Flujo con worktrees

La guía paso a paso para crear dos ramas de trabajo en paralelo está en
[`docs/worktrees.md`](docs/worktrees.md).
