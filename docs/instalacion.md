# Instalación y configuración

## Requisitos

- **PHP 8.2** o superior (extensiones `pdo_mysql` y `gd` para subir imágenes).
- **MySQL / MariaDB**. Las tablas usan **InnoDB** y codificación **`utf8_unicode_ci`**.
- **Apache con `mod_rewrite`** activado (el `.htaccess` reescribe las URLs).
- Entorno tipo **Laragon / XAMPP** sirve perfecto en local.

## Pasos

### 1. Ubicar el proyecto
Clonar/copiar dentro del `www` del servidor (ej. `C:\laragon\www\eggPHP4`).
La app asume que se sirve desde una carpeta con ese nombre (ver `_SERVER_`).

### 2. Crear la base de datos e importar el esquema
```sql
CREATE DATABASE eggbd CHARACTER SET utf8 COLLATE utf8_unicode_ci;
```
Importar en ese orden:
```bash
mysql -u root eggbd < bd/eggbd4.sql     # esquema + datos base (menús, roles, usuarios semilla)
mysql -u root eggbd < bd/tokens.sql     # tabla de tokens de la API
```
> `bd/tokens.sql` es una migración aparte porque se agregó después del dump
> original. Si regenerás `eggbd4.sql` desde phpMyAdmin, ya incluirá `tokens`.

### 3. Configurar los secretos (fuera de git)
Los secretos **no** están en el repo. Copiar la plantilla y completar:
```bash
cp core/config.local.example.php core/config.local.php
```
Editar `core/config.local.php`:
```php
define('_SERVER_DB_', '127.0.0.1');
define('_DB_', 'eggbd');
define('_USER_DB_', 'root');
define('_PASSWORD_DB_', 'tu_password');   // no dejar root sin password en producción
define('_FULL_KEY_', 'una-clave-cualquiera');
```
`config.local.php` está en `.gitignore`; **nunca se commitea**. Si falta, el
sistema muere con un mensaje claro al arrancar.

> **Nota sobre `_FULL_KEY_`:** hoy solo se usa para ofuscar variables de sesión
> (server-side) y no protege nada crítico. Podés poner cualquier valor. Si en el
> futuro hay una app móvil ya desplegada, la clave debe coincidir con la que esa
> app espera (ver [seguridad-y-api.md](seguridad-y-api.md)).

### 4. Ajustar rutas (opcional)
En `core/globals.php`:
- `_SERVER_` → la URL base del proyecto (ej. `http://127.0.0.1/eggPHP4/`).
- En `js/domain.js`, `urlweb` debe apuntar a la misma URL base.

### 5. Acceder
Abrir `http://127.0.0.1/eggPHP4/` en el navegador. Debería aparecer el login.

## Usuarios de ejemplo

El dump trae dos usuarios semilla:

| Usuario | Rol |
|---|---|
| `superadmin` | SuperAdmin (2) |
| `admin` | Admin (3) |

Las contraseñas están hasheadas con `password_hash` (bcrypt) y **no se conocen en
claro**. Si no las tenés, reseteá el hash directamente en la BD:
```sql
-- Generá el hash con: php -r "echo password_hash('nueva_clave', PASSWORD_BCRYPT);"
UPDATE usuarios SET usuario_contrasenha = '<hash_generado>' WHERE usuario_nickname = 'superadmin';
```

## Verificar que todo quedó bien
```bash
php tests/humo.php
```
Deberías ver todas las pruebas en `OK` y `Fallos: 0`. Si la BD no está disponible,
las pruebas de base de datos se omiten (no fallan).

## Checklist para producción
- [ ] `config.local.php` creado con credenciales reales (y **rotar** cualquier clave que haya estado en el historial de git).
- [ ] Servir por **HTTPS** (así la cookie de sesión activa el flag `Secure` automáticamente).
- [ ] `MySQL` con usuario dedicado y contraseña (no `root` sin clave).
- [ ] Carpeta `log/` escribible por el servidor.
