# Guía: trabajo en paralelo con `git worktree`

Esta guía deja listo el repositorio para el ejemplo de `git worktree`. La idea es tener la
misma aplicación en dos carpetas de trabajo independientes, cada una en su propia rama, sin
duplicar el repositorio ni cambiar de rama en la carpeta principal.

## Qué es un worktree

Un worktree es una carpeta de trabajo adicional asociada al **mismo** repositorio. Comparte
el historial y los objetos de `.git`, pero tiene su propio directorio, su propio índice y su
propia rama activa. Sirve para tener dos o más ramas abiertas al mismo tiempo.

En este ejemplo:

| Carpeta | Rama | Uso sugerido |
|---|---|---|
| `C:\xampp\htdocs\SDD_semana9` | `main` | Rama principal, queda intacta |
| `C:\xampp\htdocs\comentarios-frontend` | `feat/frontend-respuestas` | Cambios de interfaz de F-RES-001 |
| `C:\xampp\htdocs\comentarios-backend` | `feat/backend-seguridad` | Cambios de validación y API |

Los nombres de rama son solo una convención del ejemplo. Lo importante es que cada worktree
tenga una rama distinta de `main`, porque `main` ya está tomada por la carpeta principal.

## Requisitos previos

1. Git 2.5 o superior:

   ```powershell
   git --version
   ```

2. La carpeta principal está en `main` y sin cambios pendientes:

   ```powershell
   git status --short
   ```

   La salida debe estar vacía. Si hay cambios sin confirmar, confirmalos o guardalos antes de
   seguir: el worktree parte del último commit de la rama, no de los archivos sin confirmar.

3. La aplicación ya está incluida en el commit base de `main`. Si `git ls-tree --name-only HEAD`
   no muestra `index.html`, `api/` y `src/`, primero hay que confirmar el estado actual:

   ```powershell
   git add -A
   git commit -m "App de comentarios: base funcional con specs y documentación"
   ```

4. Las ramas y carpetas del ejemplo todavía no existen:

   ```powershell
   git branch --list 'feat/*'
   Test-Path ..\comentarios-frontend
   Test-Path ..\comentarios-backend
   ```

   `git branch` no debe listar nada y los dos `Test-Path` deben devolver `False`. Si alguna
   carpeta existe, renombrala o borrala antes de continuar: `git worktree add` no escribe sobre
   un directorio existente.

## Crear los worktrees

Desde la raíz del repositorio (`C:\xampp\htdocs\SDD_semana9`):

```powershell
git worktree add ../comentarios-frontend -b feat/frontend-respuestas
git worktree add ../comentarios-backend  -b feat/backend-seguridad
git worktree list
```

Qué hace cada comando:

- `add <ruta>` crea el directorio de trabajo en esa ruta.
- `-b <rama>` crea la rama a partir del `HEAD` actual (`main`) y la deja activa en ese worktree.
- `worktree list` muestra la carpeta principal y los worktrees con la rama y el commit de cada uno.

Salida esperada de `git worktree list`:

```
C:/xampp/htdocs/SDD_semana9        <sha> [main]
C:/xampp/htdocs/comentarios-frontend <sha> [feat/frontend-respuestas]
C:/xampp/htdocs/comentarios-backend  <sha> [feat/backend-seguridad]
```

Los tres apuntan al mismo commit al principio; a partir de ahí cada rama avanza por separado.

## Verificar que quedaron bien

```powershell
git -C ..\comentarios-frontend status
git -C ..\comentarios-backend status
git -C ..\comentarios-frontend branch --show-current
git -C ..\comentarios-backend branch --show-current
```

Cada worktree debe tener los mismos archivos de la aplicación (`index.html`, `api/`, `src/`,
`assets/`, `database/`, `specs/` y `docs/`) y una rama distinta.

## Ejecutar las tres copias

Apache sirve cualquier carpeta dentro de `htdocs`, así que las tres versiones quedan
disponibles sin configuración extra:

- Principal: <http://localhost/SDD_semana9/>
- Frontend: <http://localhost/comentarios-frontend/>
- Backend: <http://localhost/comentarios-backend/>

También se puede usar el servidor embebido de PHP, en una terminal por worktree:

```powershell
# Terminal 1
Set-Location C:\xampp\htdocs\comentarios-frontend
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8081 -t .

# Terminal 2
Set-Location C:\xampp\htdocs\comentarios-backend
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8082 -t .
```

No ejecutes dos servidores embebidos en el mismo puerto. Apache no tiene ese problema porque
distingue por ruta.

### Base de datos compartida

Los tres worktrees usan por defecto la misma base `app_comentarios_v1` y la misma tabla. Eso es
útil para la demostración: un comentario publicado desde `comentarios-frontend` se ve también
en `comentarios-backend` y en la copia principal. Los worktrees separan **código**, no datos.

Si necesitás bases separadas, `config/database.php` lee `DB_NAME`, pero `database/schema.sql`
crea y usa un nombre fijo. Habría que adaptar el esquema o crear la segunda base con las mismas
tablas antes de apuntar `DB_NAME` a ella.

## Ejercicio de demostración

El objetivo es mostrar que dos ramas pueden avanzar en paralelo sin pisarse.

1. **Frontend** — modificá solo archivos de interfaz (`assets/js/app.js`,
   `assets/css/app.css` o `index.html`). Por ejemplo, ajustar el texto del botón de respuesta.

   ```powershell
   git -C ..\comentarios-frontend status
   git -C ..\comentarios-frontend diff
   ```

2. **Backend** — modificá solo archivos de servidor (`api/` o `src/`). Por ejemplo, ampliar un
   mensaje de error de validación.

   ```powershell
   git -C ..\comentarios-backend status
   git -C ..\comentarios-backend diff
   ```

3. Comprobá el aislamiento: los cambios del worktree de frontend no aparecen en el de backend
   ni en la carpeta principal.

   ```powershell
   git status --short
   ```

4. Confirmá cada rama por separado:

   ```powershell
   git -C ..\comentarios-frontend add -A
   git -C ..\comentarios-frontend commit -m "feat(frontend): ajustes de respuestas"

   git -C ..\comentarios-backend add -A
   git -C ..\comentarios-backend commit -m "feat(backend): refuerzo de validación"
   ```

5. Volvé a la carpeta principal e integrá las dos ramas:

   ```powershell
   git merge --no-ff feat/frontend-respuestas
   git merge --no-ff feat/backend-seguridad
   ```

   Si cada worktree tocó archivos distintos, las dos integraciones son limpias. Si ambos
   tocaron la misma línea, Git marca el conflicto: resolvelo en `main`, confirmá y seguí.

6. Verificá el resultado:

   ```powershell
   git log --oneline --graph --decorate -10
   git worktree list
   ```

## Alcance sugerido para cada rama

Para que el ejemplo sea coherente con la spec y las ramas no se pisen:

| Rama | Requisitos | Archivos esperados |
|---|---|---|
| `feat/frontend-respuestas` | F-RES-001/CA-1..CA-5, RN-007, RN-008, D-06, D-07 | `index.html`, `assets/js/app.js`, `assets/css/app.css` |
| `feat/backend-seguridad` | RN-001, RN-002, RN-010, D-02, D-06 | `api/`, `src/` |

Recordá las reglas del proyecto: la validación real va en el servidor, las consultas son
parametrizadas, el texto se guarda crudo y se neutraliza al mostrar, y no se agrega
funcionalidad que no esté en `requirements.md`.

## Limpiar los worktrees

Cuando terminá el ejemplo, eliminá primero los worktrees y después las ramas ya integradas:

```powershell
git worktree remove ../comentarios-frontend
git worktree remove ../comentarios-backend
git worktree list
git branch -d feat/frontend-respuestas
git branch -d feat/backend-seguridad
```

Si un worktree tiene cambios sin confirmar, `git worktree remove` se niega. Confirmá o
descartá esos cambios, o usá `git worktree remove --force` si estás seguro.

## Problemas frecuentes

| Mensaje | Causa | Solución |
|---|---|---|
| `fatal: '<ruta>' already exists` | La carpeta destino ya existe | Renombrala o borrala; el worktree debe crear la carpeta |
| `fatal: a branch named 'feat/...' already exists` | La rama ya fue creada | Usá otro nombre o `git worktree add <ruta> feat/...` para asociarla |
| `fatal: 'main' is already checked out at ...` | Se intentó usar `main` en dos worktrees | Creá una rama nueva con `-b`, como en el ejemplo |
| `git worktree list` muestra una ruta que ya no existe | Se borró la carpeta a mano | `git worktree prune` |
| Los cambios no aparecen en el otro worktree | Cada worktree tiene su propio directorio de trabajo | Es el comportamiento esperado; integrá con `git merge` |

No borres la carpeta de un worktree a mano como forma normal de limpieza: usá
`git worktree remove` para que Git actualice su registro interno.
