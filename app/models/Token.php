<?php
/**
 * Created by PhpStorm
 * User: CESARJOSE39
 * Date: 14/10/2020
 * Time: 11:19
 *
 * Tokens de acceso para la API (app móvil).
 * Modelo: token opaco aleatorio. En la BD guardamos su sha256 (no el token en
 * claro); la app guarda el token en claro. Validar = buscar el hash y que no
 * esté vencido. Revocable borrando la fila. Sin cifrado casero.
 */
class Token extends BaseModel{
    //Días que dura un token antes de vencer.
    const DIAS_VALIDEZ = 30;
    private $encriptar;

    public function __construct()
    {
        parent::__construct();          //pdo + log (de BaseModel)
        $this->encriptar = new Encriptar();
    }

    //Crea un token para el usuario: guarda su hash y devuelve el token EN CLARO
    //(que se le entrega a la app). Devuelve false si no se pudo guardar.
    public function crear_token($id_usuario){
        $token = bin2hex(random_bytes(32)); //64 caracteres hex, alta entropía
        $ok = $this->ejecutar(
            'insert into tokens (id_usuario, token_hash, token_creacion, token_expira) values (?,?,?,?)',
            [
                $id_usuario,
                hash('sha256', $token),
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s', strtotime('+' . self::DIAS_VALIDEZ . ' days'))
            ]
        );
        return $ok ? $token : false;
    }

    //Valida un token: lo busca por su hash y que no esté vencido. Si es válido,
    //arma las variables de sesión que api.php espera y devuelve true.
    public function validar_token($token){
        $fila = $this->consultarUno(
            'select t.id_usuario, u.id_rol, u.usuario_estado from tokens t inner join usuarios u on t.id_usuario = u.id_usuario where t.token_hash = ? and t.token_expira > ? limit 1',
            [hash('sha256', (string)$token), date('Y-m-d H:i:s')]
        );
        if($fila === null){
            return false;
        }
        //api.php lee estas variables (cifradas como el resto de la sesión) tras validar.
        $_SESSION['c_u'] = $this->encriptar->encriptar($fila->id_usuario, _FULL_KEY_);
        $_SESSION['s_']  = $this->encriptar->encriptar($fila->usuario_estado, _FULL_KEY_);
        $_SESSION['ru']  = $this->encriptar->encriptar($fila->id_rol, _FULL_KEY_);
        return true;
    }

    //Revoca un token (logout de la app). Devuelve true/false.
    public function eliminar_token($token){
        return $this->ejecutar('delete from tokens where token_hash = ?', [hash('sha256', (string)$token)]);
    }
}
