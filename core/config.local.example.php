<?php
/**
 * PLANTILLA de configuración local.
 * Copiá este archivo como core/config.local.php (que NO se sube a git) y
 * completá con tus datos reales. El sistema no arranca sin config.local.php.
 */
//Conexión a la base de datos.
define('_SERVER_DB_', '127.0.0.1');
define('_DB_', 'nombre_de_tu_base');
define('_USER_DB_', 'usuario');
define('_PASSWORD_DB_', 'contraseña');
//Clave de cifrado (cualquier cadena). Si ya hay una app móvil usando la API,
//debe coincidir con la clave que la app espera.
define('_FULL_KEY_', 'cambia-esta-clave');
