# Documentación de eggPHP4

Framework MVC propio en PHP puro (sin frameworks de terceros), pensado para
paneles de administración con control de acceso por roles y una API para apps
móviles. Este documento es el mapa general; los detalles están en los docs
enlazados abajo.

## Índice

- [Instalación y configuración](instalacion.md) — requisitos, `config.local.php`, base de datos.
- [Guía de desarrollo](desarrollo.md) — convenciones, clases base, cómo agregar controladores/modelos/vistas, tests.
- [Seguridad y API](seguridad-y-api.md) — sesiones, CSRF, permisos, y cómo consumir la API con token.
- [Composer](composer.md) — cómo importar librerías de terceros el día que haga falta.

---

## Qué es y qué principios sigue

- **PHP puro, sin dependencias.** El autoloader es propio (`core/autoload.php`), no hay `vendor/` salvo que se decida usar Composer para librerías puntuales.
- **Legible por encima de todo.** Se evita "magia" (Reflection, ORMs, contenedores DI). Todo el código se puede leer y entender de principio a fin.
- **Convención sobre configuración.** El nombre del controlador y de la función determinan la ruta; los permisos viven en la base de datos.

Requisitos mínimos: **PHP 8.2**, **MySQL/MariaDB** (InnoDB, `utf8_unicode_ci`) y **Apache con `mod_rewrite`** (para el `.htaccess`).

---

## Estructura de carpetas

| Carpeta / archivo | Rol |
|---|---|
| `index.php` | Punto de entrada **web**. Devuelve HTML. |
| `api.php` | Punto de entrada de la **API**. Devuelve JSON. |
| `.htaccess` | Reescribe URLs amigables → `?c=Controlador&a=accion&id=`. |
| `core/` | Infraestructura: `bootstrap.php`, `globals.php`, `config.local.php` (secretos, fuera de git), `autoload.php`, `Database.php`, `session.php`, `BaseModel.php`, `BaseController.php`, `ResultCode.php`. |
| `app/controllers/` | Controladores `XxxController.php`. |
| `app/models/` | Modelos de datos (`Usuario`, `Rol`, `Menu`…) y servicios (`Validar`, `Encriptar`, `Token`, `Log`, `Sesion`, `Navbar`, `Menui`, `Archivo`). |
| `app/view/` | Vistas `.php` por controlador + parciales (`header.php`, `navbar.php`, `footer.php`). |
| `js/` | JavaScript por módulo, que consume la API. |
| `libs/`, `styles/`, `media/` | Librerías front vendorizadas, estilos, y archivos subidos. |
| `bd/` | Esquema SQL (`eggbd4.sql`) y migración de tokens (`tokens.sql`). |
| `tests/` | Red de pruebas de humo (`humo.php`). |
| `docs/` | Esta documentación. |
| `log/` | Registro de errores (autogenerado, fuera de git). |

---

## Flujo de una request

Ambos entrypoints comparten el arranque (`core/bootstrap.php`: config + autoloader +
manejo de errores + sesión endurecida + token CSRF + `$log`) y difieren en el transporte.

### Web (`index.php`)
1. `.htaccess` mapea `/Usuario/inicio` → `index.php?c=Usuario&a=inicio`.
2. `bootstrap.php` arranca todo. `$_SESSION['acceso'] = 1`.
3. `core/session.php` refresca los datos del usuario logueado (server-side).
4. Se resuelve el **controlador** (`$_GET['c']`, por defecto `Admin` si hay sesión, `Login` si no) y la **acción** (`$_GET['a']`, por defecto `inicio`).
5. Se comprueba el permiso con `Menui::verificar_permiso_usuario(rol, controlador, accion)` y que el usuario esté habilitado.
6. Según el resultado: `ok` → se instancia el controlador y se llama `$controller->$accion()`; `login` → vista de login; `error` → `ErrorController`.
7. Los métodos de "vista" hacen `require` de `header → navbar → vista → footer`.

### API (`api.php`)
1. `.htaccess` mapea `/api/usuario/guardar_nuevo_usuario` → `api.php?c=usuario&a=guardar_nuevo_usuario`.
2. `bootstrap.php` arranca. `$_SESSION['acceso'] = 0`. Cabeceras CORS.
3. **Autenticación**:
   - Si hay sesión web (`$_SESSION['ru']`) → se valida con `verificar_permiso_usuario_api`.
   - Si es la app (`$_POST['app']` + `$_POST['tn']`) → `Token::validar_token` arma la sesión y se valida el permiso.
4. **CSRF**: los POST del navegador (no la app) deben traer `X-CSRF-Token`.
5. `ok` → se instancia el controlador y se llama la acción, que **siempre** devuelve JSON con el formato estándar (ver abajo).

### Formato de respuesta JSON
Todas las "funciones" de los controladores responden con:
```json
{ "result": { "code": 1, "message": "OK" } }
```
Los códigos están nombrados en `core/ResultCode.php`: `OK=1`, `ERROR=2`,
`DUPLICADO=3` (o `CREDENCIALES_INVALIDAS=3` en login), `CORREO_DUPLICADO=4`,
`DATOS_INVALIDOS=6`. El login para app agrega un campo `data` hermano con los
datos del usuario y el token.

---

## Modelo de permisos (roles, menús, opciones, permisos, restricciones)

El control de acceso es **dirigido por datos** en la base de datos. Piezas:

| Tabla | Qué representa |
|---|---|
| `roles` | Los roles. Semilla: `1` Libre (anónimo), `2` SuperAdmin, `3` Admin. |
| `menus` | Un menú del panel. `menu_controlador` = **nombre del controlador**. |
| `opciones` | Una vista/página bajo un menú. `opcion_funcion` = **función de vista** del controlador (web). |
| `permisos` | Una acción de API bajo una opción. `permiso_accion` = **función de API** del controlador. |
| `roles_menus` | Da a un rol acceso a un menú (y por lo tanto a su controlador). |
| `restricciones` | Quita a un rol el acceso a una opción concreta (mecanismo inverso). |

Cómo se evalúa un acceso:

- **Web** (`verificar_permiso_usuario`): el rol debe tener el menú (vía `roles_menus`),
  el menú debe apuntar al controlador pedido, debe existir una `opcion` con
  `opcion_funcion = accion` (todo con estado `1`), y **no** haber una `restriccion`
  para ese rol y esa opción.
- **API** (`verificar_permiso_usuario_api`): igual, pero además debe existir un
  `permiso` con `permiso_accion = accion` bajo la opción, respetando restricciones.

En resumen: **un rol ve un controlador si tiene el menú; puede abrir una vista si
existe la opción y no está restringida; puede llamar una función de API si existe
el permiso.** El rol `1` (Libre) es el que se usa para accesos sin login.

---

## Red de pruebas

`php tests/humo.php` corre pruebas de humo sin framework (cifrado, validaciones,
`ResultCode`, y —si hay BD— lecturas de modelos, equivalencia de la consulta del
navbar y el round-trip de tokens). Correla antes y después de cualquier cambio.
