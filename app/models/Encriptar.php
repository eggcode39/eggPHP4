<?php
/**
 * Created by PhpStorm
 * User: CESARJOSE39
 * Date: 17/09/2020
 * Time: 18:08
 */
class Encriptar{
    function __construct()
    {

    }
    //Funcion que se encarga de ENCRIPTAR el texto entrante
    public function encriptar($string, $key) {
        $result = '';
        //Un valor null se trata como cadena vacía: evita el deprecation de strlen(null)
        //en PHP 8.1+ cuando se cifra un campo opcional vacío (ej. apellido materno, email).
        $string = (string)$string;
        //Recorremos todo el string recibido
        for($i=0; $i < strlen($string); $i++) {
            //Extraemos el caracter del string que vamos a encriptar
            $char = substr($string, $i, 1);
            //Extraemos un caracter de nuestra llave
            $keychar = substr($key, ($i % strlen($key)) - 1, 1);
            //Convertirmos $char y $keychar a un valor entre 0 y 255, y luego lo sumamos
            //para obtener un valor que usaremos con la funcion chr para obtener un valor ASCII especifico
            $char = chr(ord($char) + ord($keychar));
            //Aumentamos el valor de $char a nuestro result
            $result .= $char;
        }
        //Codificamos la cadena a base64 y la devolvemos
        return base64_encode($result);
    }
    //Funcion que se encarga de DESENCRIPTAR el texto entrante
    public function desencriptar($string, $key) {
        $result = '';
        //Verificamos que el string no este nulo
        if(!empty($string)){
            //Decodificamos el string que recibimos
            $string = base64_decode($string);
            //Recorremos todo el string decodificado
            for($i=0; $i<strlen($string); $i++) {
                //Extraemos un caracter de nuestro string
                $char = substr($string, $i, 1);
                //Extraemos un caracter de nuestra llave
                $keychar = substr($key, ($i % strlen($key)) - 1, 1);
                //Convertirmos $char y $keychar a un valor entre 0 y 255 inverso a la encriptacion, y luego lo sumamos
                //para obtener un valor que usaremos con la funcion chr para obtener un valor ASCII especifico
                $char = chr(ord($char)-ord($keychar));
                //Aumentamos el valor de $char a nuestro result
                $result .= $char;
            }
        }
        //Devolvemos la cadena obtenida
        return $result;
    }
    //Las funciones de "encriptación triple" para tokens se eliminaron (C4):
    //el token de la app ahora es opaco y vive en la tabla `tokens` (ver Token.php).
}