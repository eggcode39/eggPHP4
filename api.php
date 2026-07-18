<?php
/**
 * Created by PhpStorm
 * User: CESARJOSE39
 * Date: 17/09/2020
 * Time: 18:03
 */
//Gestión de Acceso a WebServices
//Arranque común: config, autoloader, librerías, manejo de errores, sesión y $log.
require 'core/bootstrap.php';
//Variable para definir el tipo de acceso
$_SESSION['acceso'] = 0;
//Variable para correr o no el sistema si no está en mantenimiento
$correr = true;
//Valida si el sistema está en mantenimento o no
//Para acceder al modo de superadmin en mantenimiento es lo siguiente:
//api.php?c=(controlador)&a=(funcion)&(nombre_parametro_get)=superadmin
//Se puede modificar el nombre de la variable de $_GET['test'], por seguridad
if(_MANTENIMIENTO_WS == 1){
    if(!isset($_SESSION['web']) || $_SESSION['web'] != true){
        if(!isset($_GET['test']) || $_GET['test'] != true){
            $correr = false;
        }
    }
}
//Variables a usar para inicializar los controladores y funciones
$function_action = "Desconocido";
$archivo = "";
$controlador = "";
$accion = "";
//Array para validar situacion de accesos a vista
$validacion = array("estado" => 'error',"accion" => '',"mensaje" => 'Error General');
if($correr){
    try{
        //Para Mostrar o No Errores (Comentado Para SI, Descomentado Para NO)
        //error_reporting(E_ALL);

        //Las clases (Database, Validar, Encriptar, Token, Menui, Sesion) se cargan
        //solas vía core/autoload.php en cuanto se instancian más abajo.
        //Inicialización de clases necesarias en Index
        $token = new Token();
        $menui = new Menui();
        $encriptar = new Encriptar();

        //Para manejo de caracteres
        header("Content-Type: text/html;charset=utf-8");
        //Para Permitir CORS
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        header('Access-Control-Allow-Methods: GET, POST');
        if(isset($_SESSION['web']) && $_SESSION['web'] == true){
            //Verificación de Variables de Sesion y Cookies
            $sesion = new Sesion();
            require 'core/session.php';
        }
        //CSRF: los POST del navegador deben traer el token de sesión en la cabecera X-CSRF-Token.
        //La app móvil se autentica con $_POST['app'] + token y queda exenta de esta comprobación.
        if($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['app'])){
            $csrf_enviado = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if(empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf_enviado)){
                $log->insertar('CSRF token inválido o ausente', 'api|csrf');
                echo json_encode(array("result" => array("code" => 2, "message" => 'Token de seguridad inválido. Recargá la página.')));
                exit;
            }
        }
        //Inicio de Código de Verificación de Permisos

        //Captura de Datos para Obtener el Controlador y la Accion
        //Por Aquí Pasan Todas Las Funciones Para La Lectura de Vistas
        //Recepción del Controlador Enviado
        if(isset($_GET['c'])){
            //Aqui se recibe el controlador, si está declarado
            $controlador = $_GET['c'];
            //Tratamiento de Caracteres
            $controlador = trim(ucfirst($controlador));
            $controlador = filter_var($controlador, FILTER_SANITIZE_SPECIAL_CHARS);
        } else {
            //Si no hay Controlador declarado, se genera error y se detiene el código
            $response = array("code" => 2,"message" => 'Controlador mal especificado');
            $data = array("result" => $response);
            echo json_encode($data);
            exit;
        }
        //Recepción de la Función/Acción Enviada
        if(isset($_GET['a'])){
            //Aqui la Función/Acción, si está declarado
            $accion = $_GET['a'];
            //Tratamiento de Caracteres
            $accion = trim($accion);
            $accion = filter_var($accion, FILTER_SANITIZE_SPECIAL_CHARS);
        } else {
            //Si no hay Función/Acción declarada, se genera error y se detiene el código
            $response = array("code" => 2,"message" => 'Acción mal especificada');
            $data = array("result" => $response);
            echo json_encode($data);
            exit;
        }
        $function_action = $controlador . "|" . $accion;

        //Verificar existencia de los archivos
        $archivo = 'app/controllers/' . $controlador . 'Controller.php';
        //Verifica Si El Archivo Existe
        if(file_exists($archivo)){
            //Variable Para Determinar Si Procede o No La Petición
            $autorizado = false;

            if(isset($_SESSION['ru'])){
                $rol = $encriptar->desencriptar($_SESSION['ru'], _FULL_KEY_);
                $autorizado = $menui->verificar_permiso_usuario_api($rol, $controlador, $accion);
                $permiso = $encriptar->desencriptar($_SESSION['s_'],_FULL_KEY_);
            } else {
                //Parte del codigo que valida el token
                if(isset($_POST['app']) && $_POST['app'] == true){
                    if(isset($_POST['tn'])) {
                        //Función que verifica si el token proporcionado es válido
                        if($token->validar_token($_POST['tn'])){
                            $rol = $encriptar->desencriptar($_SESSION['ru'], _FULL_KEY_);
                            $autorizado = $menui->verificar_permiso_usuario_api($rol, $controlador, $accion);
                            $permiso = $encriptar->desencriptar($_SESSION['s_'],_FULL_KEY_);
                        } else {
                            //Si_entra_aqui = "Es porque el token no vale";
                            $permiso = 0;
                            $autorizado = 2;
                        }
                    } else {
                        $autorizado = $menui->verificar_permiso_usuario_api(1, $controlador, $accion);
                        $permiso = 1;
                    }
                } else {
                    $autorizado = $menui->verificar_permiso_usuario_api(1, $controlador, $accion);
                    $permiso = 1;
                }
            }
            //Si $autorizado =  true Entra Aquí, Descomentar La Linea Siguiente Si Sólo Se Quiere Probar Funciones
            //$autorizado = true;
            //$permiso = 1;
            if($autorizado && $permiso == 1){
                //Entra Aquí Si La Clase Y La Funcion Existen
                $validacion['estado'] = 'ok';
                $validacion['mensaje'] = '';
            } else {
                //Entramos aquí para validar el tipo de acceso que esta mal
                if($permiso == 0){
                    if($autorizado == 2){
                        //LLEGA AQUI SI EL TOKEN ES INVALIDO
                        $validacion['mensaje'] = 'Token Invalido';
                    } else {
                        //LLEGA AQUI SI EL USUARIO FUE INHABILITADO
                        $validacion['mensaje'] = 'Usuario Inhabilitado';
                    }

                } else {
                    //Si Permiso == 1, entra aquí porque autorizado = 0
                    $validacion['mensaje'] = 'Sin Permisos Para Acceder a la Funcion o Funcion Deshabilitada';
                }
            }
        } else {
            //Si el Archivo No Existe, Genera El Error Y Notifica En La Pantalla
            $validacion['mensaje'] = 'Archivo llamado al controlador no existe';
        }
    } catch (Exception $e){
        //Error en Try/Catch
        //Acciones si el archivo no existe
        //Automaticamente, notificar error
        $validacion['mensaje'] = $e->getMessage();
    }
} else {
    session_destroy();
    $validacion['estado'] = 'error';
    $validacion['accion'] = 'mantenimiento';
    $validacion['mensaje'] = 'Sistema en mantenimiento, intente en un rato';
}

switch ($validacion['estado']){
    case 'ok':
        try{
            //Entra Aquí Si La Clase Y La Funcion Existen
            require $archivo;
            $clase = sprintf('%sController', $controlador);
            $controller = new $clase;
            $controller->$accion();
            if(isset($_POST['app']) && $_POST['app'] == true){
                unset($_SESSION['c_u']);
                unset($_SESSION['s_']);
                unset($_SESSION['ru']);
            }
        } catch (Throwable $e){
            //Si no hay Controlador declarado, se genera error y se detiene el código
            $log->insertar($e->getMessage(), $function_action);
            $response = array("code" => 2,"message" => $e->getMessage());
            $data = array("result" => $response);
            echo json_encode($data);
            exit;
        }
        break;
    default:
        //Si no hay Controlador declarado, se genera error y se detiene el código
        $log->insertar($validacion['mensaje'], $function_action);
        switch ($validacion['accion']){
            case 'mantenimiento':
                $code = 85;
                break;
            default:
                $code = 2;
                break;
        }
        $response = array("code" => $code,"message" => $validacion['mensaje']);
        $data = array("result" => $response);
        echo json_encode($data);
        break;
}