# Seguridad y API

Modelo de seguridad del framework y cómo consumir la API desde una app.

## Sesiones (web)

- **La sesión vive en el servidor.** El navegador solo tiene el ID de sesión de PHP.
- La **cookie de sesión** se configura endurecida en `core/bootstrap.php`:
  `HttpOnly` (no accesible por JS → resiste XSS), `SameSite=Lax` (no viaja en POST
  cross-site → mitiga CSRF) y `Secure` cuando se sirve por HTTPS.
- Al **iniciar sesión** se llama `session_regenerate_id(true)` para cerrar
  *session fixation*.
- `core/session.php` refresca en cada request los datos del usuario logueado
  desde `$_SESSION` (server-side). **No hay cookie "recordarme"**: se eliminó
  porque era falsificable.

> Las variables dentro de `$_SESSION` se guardan "cifradas" con un XOR simple
> (`Encriptar`). Como la sesión es server-side, eso es **ofuscación, no seguridad**
> — no protege nada crítico y no hace falta confiar en ello.

## CSRF

Todas las escrituras del navegador pasan por `api.php` con la cookie de sesión, así
que se protegen con un token CSRF:

1. `bootstrap.php` genera un token por sesión: `$_SESSION['csrf'] = bin2hex(random_bytes(32))`.
2. Se expone en el `<head>` como `<meta name="csrf-token" content="...">`
   (en `header.php` y en la vista de login).
3. `js/domain.js` lo adjunta a **todas** las peticiones ajax vía
   `$.ajaxSetup` en la cabecera `X-CSRF-Token`.
4. `api.php` valida con `hash_equals` en los **POST del navegador**. La app móvil
   (que se autentica con token) queda **exenta**.

> Si al guardar aparece *"Token de seguridad inválido"*, casi siempre es el
> `domain.js` cacheado: forzá recarga (Ctrl+F5).

## Autenticación de contraseñas

Se usan `password_hash()` (bcrypt) y `password_verify()`. Nunca se guardan
contraseñas en claro ni reversibles.

## Permisos

El acceso se resuelve contra la base de datos (roles → menús → opciones → permisos,
con restricciones). Ver el detalle en el [README](README.md#modelo-de-permisos).
La comprobación corre en cada request **antes** de instanciar el controlador.

---

## API para app móvil (tokens)

La API (`api.php`) permite que una app se autentique con un **token opaco**, sin
usar la cookie de sesión del navegador.

### Modelo del token
- Es una cadena aleatoria de 64 caracteres hex (`random_bytes(32)`).
- En la BD (tabla `tokens`) se guarda su **sha256**, no el token en claro: si se
  filtra la base, los tokens no son utilizables.
- Tiene **expiración** (30 días por defecto, `Token::DIAS_VALIDEZ`).
- Es **revocable**: borrar la fila lo invalida (`Token::eliminar_token`).

### 1. Login desde la app
`POST api/login/validar_sesion` con:
```
usuario_nickname = <nick>
usuario_contrasenha = <clave>
app = true
```
Respuesta (si las credenciales son válidas, `code = 1`):
```json
{
  "result": { "code": 1, "message": "OK" },
  "data": {
    "c_u": 5, "ru": 3, "rn": "Admin", "_n": "juan",
    "u_e": "juan@correo.com", "u_i": "http://.../media/usuarios/...jpg",
    "tn": "e6cf...<64 hex>"
  }
}
```
La app **guarda `data.tn`** (el token). No es la cookie CSRF; los POST con `app=true`
no llevan CSRF.

### 2. Llamadas siguientes
En cada request a la API, la app manda:
```
app = true
tn  = <el token guardado>
... (parámetros de la función)
```
El servidor busca el hash del token, verifica que no esté vencido, arma la sesión
del usuario y evalúa el permiso (`verificar_permiso_usuario_api`). Si el token es
inválido o venció, responde con error de token.

### 3. Códigos relevantes
- `1` OK · `2` error general · `3` credenciales inválidas · `6` datos inválidos.
- A nivel de acceso, la API distingue: token inválido, usuario inhabilitado, y sin
  permisos para la función.

### Notas de implementación
- La generación del token está en `Token::crear_token($id_usuario)` (lo llama el
  login cuando `app=true`).
- La validación está en `Token::validar_token($tn)`.
- No hay endpoint de logout de app por ahora; el token simplemente expira. Si se
  quiere logout inmediato, exponer una función que llame `Token::eliminar_token`.

---

## Pendiente / conocido

- **Historial de git:** credenciales y clave estuvieron en `globals.php` en commits
  viejos. `config.local.php` frena la exposición futura, pero conviene **rotar la
  contraseña de MySQL**.
- **XOR de sesión:** el cifrado casero de las variables de `$_SESSION` es
  ofuscación inofensiva; se puede quitar en una limpieza futura (pasar la sesión a
  texto plano) sin impacto de seguridad, ya que es server-side.
