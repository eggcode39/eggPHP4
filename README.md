# eggPHP4

Framework MVC propio en **PHP puro** (sin dependencias), para paneles de
administración con control de acceso por roles y una API para apps móviles.
Desarrollado por mí, para mí (y BufeoTEC).

Principios: **legibilidad y control total** por encima de todo — sin "magia"
(nada de ORMs, contenedores DI ni Reflection), autoloader propio, y todo el
código legible de principio a fin.

## Arranque rápido

1. Copiá el proyecto en el `www` del servidor (Laragon/XAMPP).
2. Creá la BD e importá `bd/eggbd4.sql` y `bd/tokens.sql`.
3. Copiá `core/config.local.example.php` → `core/config.local.php` y completá tus datos.
4. Abrí `http://127.0.0.1/eggPHP4/`.
5. Verificá con `php tests/humo.php`.

Detalle completo en [docs/instalacion.md](docs/instalacion.md).

## Documentación

- 📖 [Visión general y arquitectura](docs/README.md) — estructura, flujo de request, modelo de permisos.
- ⚙️ [Instalación y configuración](docs/instalacion.md)
- 🛠️ [Guía de desarrollo](docs/desarrollo.md) — convenciones, clases base, cómo agregar controladores/modelos/vistas.
- 🔐 [Seguridad y API](docs/seguridad-y-api.md) — sesiones, CSRF, permisos, tokens de la app.
- 📦 [Composer](docs/composer.md) — para importar librerías el día que haga falta.

## Requisitos

PHP 8.2+, MySQL/MariaDB (InnoDB, `utf8_unicode_ci`), Apache con `mod_rewrite`.
