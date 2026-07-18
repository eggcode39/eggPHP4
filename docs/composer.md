# Composer en eggPHP4 — solo para librerías de terceros

En este proyecto Composer **NO reemplaza** tu autoloader. Tu `core/autoload.php`
sigue cargando todas TUS clases (modelos, controladores, core). Composer se usa
**únicamente** para traer librerías de terceros el día que las necesites.

Los dos conviven sin problema: PHP permite varios autoloaders registrados a la
vez (`spl_autoload_register` es una pila). El tuyo carga tus clases (sin
namespace, en `app/` y `core/`); el de Composer carga las librerías (con
namespace, en `vendor/`). No chocan.

---

## Ya está todo preparado

No hay que configurar nada más. El proyecto ya trae:

- **`composer.json`** — declara solo el requisito de PHP. Sin sección `autoload`
  (a propósito: tus clases las maneja `core/autoload.php`, no Composer).
- **`index.php` y `api.php`** — incluyen `vendor/autoload.php` **solo si existe**:

  ```php
  require 'core/autoload.php';                       // tus clases (siempre)
  if (file_exists(__DIR__ . '/vendor/autoload.php')) {
      require __DIR__ . '/vendor/autoload.php';       // librerías (si las hay)
  }
  ```

- **`.gitignore`** — ignora `vendor/` (no se sube a git; se regenera).

Hoy `vendor/` no existe y la guardia `file_exists` hace que no pase nada. Todo
corre igual que siempre.

---

## El día que quieras una librería (este es TODO el proceso)

```bash
# 1. Instalar la librería (esto crea vendor/ la primera vez)
composer require nombre/libreria

# 2. Usarla en tu código, con su namespace
```

Ejemplo real — registrar logs con Monolog:

```bash
composer require monolog/monolog
```

```php
// En cualquier clase tuya:
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$log = new Logger('app');
$log->pushHandler(new StreamHandler('log/app.log'));
$log->warning('algo pasó');
```

Eso es todo. No tocas `index.php`, ni tu autoloader, ni nada más. La guardia
`file_exists` ya detecta `vendor/` y carga la librería automáticamente.

---

## Comandos que usarías (pocos)

| Quiero... | Comando |
|---|---|
| Agregar una librería | `composer require proveedor/libreria` |
| Quitar una librería | `composer remove proveedor/libreria` |
| Reinstalar en otra PC/servidor | `composer install` |
| Ver qué tengo instalado | `composer show` |

Para escribir controladores, modelos y vistas **no usas Composer para nada**.
Solo aparece cuando quieres meter código de terceros.

---

## Reglas mentales

- **`vendor/` no se lee ni se edita ni se sube a git.** Es como `node_modules`:
  resultado de una herramienta. Tu código sigue siendo 100% tuyo en `app/` y `core/`.
- **`composer.json` y `composer.lock` SÍ van a git.** Describen qué instalar.
  Quien clone el repo corre `composer install` y Composer reconstruye `vendor/`.
- **Tu cifrado, seguridad, etc.:** muchas cosas que parecen "necesitar librería"
  ya vienen en PHP 8 (ej. `sodium` para cifrado). Mira primero lo nativo antes de
  agregar una dependencia.

---

## Si algún día SÍ quisieras que Composer cargue también tus clases

No es necesario, pero es posible (PSR-4 con namespaces). Es un cambio mayor
—ponerle namespace a cada clase— y no aporta nada mientras tu `core/autoload.php`
funcione. Queda anotado como opción futura, no como pendiente.
