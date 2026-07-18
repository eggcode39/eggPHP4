<?php
/**
 * Arranque común de los puntos de entrada (index.php y api.php).
 *
 * Reúne lo idéntico que antes se repetía en ambos: configuración, autoloader,
 * librerías de Composer (si las hay), manejo de errores, sesión y el log.
 * Lo específico de cada uno (web vs API: cabeceras, permisos, respuesta) se
 * queda en su propio archivo.
 *
 * Deja disponibles en el archivo que lo incluye: la sesión abierta y $log.
 */
//Configuración y constantes globales.
require __DIR__ . '/globals.php';
//Autocarga de NUESTRAS clases (modelos, controladores, core).
require __DIR__ . '/autoload.php';
//Autocarga de librerías de terceros vía Composer, SOLO si están instaladas.
//Hoy vendor/ no existe y no pasa nada; el día que hagas "composer require X", se activa solo.
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
}
//Manejo de errores personalizado: manda warnings/deprecations de PHP a nuestro log.
set_error_handler("exception_error_handler");
//Endurecimiento de la cookie de sesión (debe ir ANTES de session_start):
//  httponly => el JS no puede leerla (protege ante XSS)
//  samesite Lax => no viaja en POST desde otros sitios (mitiga CSRF)
//  secure => sólo por HTTPS (en local http queda false automáticamente)
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
//Inicio de sesión.
session_start();
//Log para registro de errores, disponible como $log en index.php / api.php.
$log = new Log();
//Token CSRF por sesión: se genera una vez y protege los POST del navegador.
//(random_bytes es nativo de PHP; no necesita librerías.)
if(empty($_SESSION['csrf'])){
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
