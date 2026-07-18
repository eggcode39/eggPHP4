<?php
/**
 * Clase base de los controladores.
 *
 * Crea en el constructor las dependencias que TODOS los controladores comparten
 * (log, encriptar, sesion, validar), para no repetirlas en cada uno. Las
 * dependencias propias de cada controlador (sus modelos concretos) se crean en
 * su propio constructor, que primero llama a parent::__construct().
 *
 * También ofrece responder(): arma y envía la respuesta JSON estándar.
 */
class BaseController {
    protected $log;
    protected $encriptar;
    protected $sesion;
    protected $validar;

    public function __construct() {
        $this->log = new Log();
        $this->encriptar = new Encriptar();
        $this->sesion = new Sesion();
        $this->validar = new Validar();
    }

    // Envía la respuesta JSON estándar del framework:
    //   {"result":{"code":<code>,"message":<message>, ...extra}}
    // $extra son campos opcionales que van DENTRO de "result" (ej. "usuario", "menu").
    protected function responder($code, $message = 'OK', $extra = []) {
        $result = array_merge(['code' => $code, 'message' => $message], $extra);
        echo json_encode(['result' => $result]);
    }
}
