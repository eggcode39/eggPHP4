<?php
/**
 * Códigos de resultado que devuelven controladores y modelos al cliente.
 *
 * Son los MISMOS números de siempre, sólo que con nombre para que el código se
 * lea. El cliente (JS / app) sigue recibiendo el número (1, 2, 3, ...): la API
 * no cambia en nada.
 *
 * Se usa una clase con constantes (no un enum formal) a propósito: así los
 * códigos siguen siendo int y conviven sin fricción con los `== 1` y los
 * `return ? 1 : 2` que ya existían.
 */
class ResultCode {
    const OK = 1;                 // todo salió bien
    const ERROR = 2;              // error general / la operación falló
    const DUPLICADO = 3;          // ya existe un registro igual (nickname, controlador, rol...)
    const CREDENCIALES_INVALIDAS = 3; // mismo código 3, pero en el login: usuario o contraseña incorrectos
    const CORREO_DUPLICADO = 4;   // ya existe ese correo
    const DATOS_INVALIDOS = 6;    // la validación de parámetros falló
}
