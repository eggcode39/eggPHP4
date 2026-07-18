<?php
/**
 * Created by PhpStorm
 * User: CESARJOSE39
 * Date: 17/09/2020
 * Time: 18:04
 */
//En caso el sistema sea consumido por app móviles, se puede inhabilitar
//el acceso a los WS por este medio
define('_MANTENIMIENTO_WS', 0);
//Si el sistema se encuentra en mantenimiento, habilitamos
//aqui para que nadie pueda acceder a la misma
define('_MANTENIMIENTO_WEB', 0);

//Variables globales de uso en todo el sistema
//Establecer Zona Horaria
date_default_timezone_set('America/Lima');
//Definicion de servidor del aplicativo
define('_SERVER_', 'http://127.0.0.1/eggPHP4/');
//Credenciales de BD y clave de cifrado: viven en core/config.local.php (fuera de git).
if(!file_exists(__DIR__ . '/config.local.php')){
    die('Falta core/config.local.php — copiá core/config.local.example.php como config.local.php y completá tus datos.');
}
require __DIR__ . '/config.local.php';
//Titulo
define('_TITLE_', 'EggPHP4');
//Rutas de Archivos
define('_STYLES_ALL_', 'styles/');
define('_STYLES_ADMIN_', 'styles/admin/');
define('_STYLES_LOGIN_', 'styles/login/');
define('_STYLES_INDEX_', 'styles/inicio/');
define('_ICON_', 'styles/bufeotec-original.png');
define('_JS_','js/');
define('_VIEW_PATH_', 'app/view/');
define('_LIBS_', 'libs/');
//Version
define('_VERSION_','0.1');
define('_MYSITE_','https://bufeotec.com');

// Manejo de Errores Personalizado de PHP a Try/Catch
function exception_error_handler($severidad, $mensaje, $fichero, $linea) {
    $cadena =  '[LEVEL]: ' . $severidad . ' IN ' . $fichero . ': ' . $linea . '[MESSAGGE]' . $mensaje . "\n";
    $log = new Log();
    $log->insertar($cadena, "Excepcion No Manejada");
    //echo $cadena;
}