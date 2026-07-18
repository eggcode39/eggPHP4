<?php
/**
 * Created by PhpStorm
 * User: CESARJOSE39
 * Date: 17/09/2020
 * Time: 18:05
 */
//Refresca los datos del usuario en la sesión en cada request, leyéndolos desde
//$_SESSION (que vive en el SERVIDOR). Ya NO se usa la cookie "recordarme" c_u:
//era falsificable con la clave pública (agujero C1) y se eliminó.
if(!isset($_POST['app'])){
    if(isset($_SESSION['c_u'])){
        //Hay sesión iniciada: refrescamos los datos del usuario desde la BD.
        $usuario = $sesion->obtener_informacion($encriptar->desencriptar($_SESSION['c_u'], _FULL_KEY_));
        if($usuario){
            $sesion->generar_sesion($usuario);
        } else {
            //El usuario ya no existe o quedó deshabilitado: cerramos la sesión.
            $sesion->cerrar_sesion();
        }
    }
    //Si no hay $_SESSION['c_u'] es un visitante anónimo: no hay nada que refrescar.
} else {
    unset($_SESSION['c_u']);
    unset($_SESSION['s_']);
    unset($_SESSION['ru']);
}
