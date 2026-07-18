<?php
/**
 * Created by PhpStorm
 * User: CESARJOSE39
 * Date: 26/10/2020
 * Time: 23:21
 */
class Usuario extends BaseModel{
    //Campos usados cuando esta clase actúa como contenedor de datos (DTO) en los controladores.
    //Quedan en null hasta que se asignan; un id en null indica "registro nuevo" en los guardar_*.
    //--- persona ---
    public $id_persona;
    public $persona_nombre;
    public $persona_apellido_paterno;
    public $persona_apellido_materno;
    public $persona_nacimiento;
    public $persona_telefono;
    public $person_codigo;
    //--- usuario ---
    public $id_usuario;
    public $id_rol;
    public $usuario_nickname;
    public $usuario_contrasenha;
    public $usuario_email;
    public $usuario_imagen;
    public $usuario_estado;
    //El constructor (pdo + log) y los ayudantes consultar/consultarUno/ejecutar vienen de BaseModel.

    //Listamos todos los usuarios del sistema, excepto los superadmin (id_rol 2)
    public function listar_usuarios(){
        return $this->consultar('select * from usuarios u inner join personas p on u.id_persona = p.id_persona inner join roles r on u.id_rol = r.id_rol where u.id_rol <> 2');
    }
    //Lista la información del usuario según id
    public function listar_usuario($id_usuario){
        return $this->consultarUno('select u.id_usuario, u.id_persona, u.id_rol, u.usuario_nickname, u.usuario_email, u.usuario_imagen, u.usuario_estado, p.persona_nombre, p.persona_apellido_paterno, p.persona_apellido_materno, p.persona_nacimiento, p.persona_telefono, r.rol_nombre from usuarios u inner join personas p on u.id_persona = p.id_persona inner join roles r on u.id_rol = r.id_rol where u.id_usuario = ?', [$id_usuario]);
    }
    //Listamos todos los usuarios del sistema (incluye superadmin)
    public function listar_usuarios_superadmin(){
        return $this->consultar('select * from usuarios u inner join personas p on u.id_persona = p.id_persona inner join roles r on u.id_rol = r.id_rol');
    }
    //Validar nickname (true si ya existe)
    public function validar_nickname($usuario_nickname){
        $fila = $this->consultarUno('select id_usuario from usuarios where usuario_nickname = ? limit 1', [$usuario_nickname]);
        return $fila !== null && isset($fila->id_usuario);
    }
    //Validar correo (true si ya existe)
    public function validar_correo($usuario_email){
        $fila = $this->consultarUno('select id_usuario from usuarios where usuario_email = ? limit 1', [$usuario_email]);
        return $fila !== null && isset($fila->id_usuario);
    }
    //Validar nickname en edicion (true si ya existe en OTRO usuario)
    public function validar_nickname_edicion($usuario_nickname, $id_usuario){
        $fila = $this->consultarUno('select id_usuario from usuarios where usuario_nickname = ? and id_usuario <> ? limit 1', [$usuario_nickname, $id_usuario]);
        return $fila !== null && isset($fila->id_usuario);
    }
    //Validar correo en edicion (true si ya existe en OTRO usuario)
    public function validar_correo_edicion($usuario_email, $id_usuario){
        $fila = $this->consultarUno('select id_usuario from usuarios where usuario_email = ? and id_usuario <> ? limit 1', [$usuario_email, $id_usuario]);
        return $fila !== null && isset($fila->id_usuario);
    }
    //Listar persona por microtime. Devuelve el id_persona, o 0 si no se encuentra.
    public function listar_persona_microtime($persona_codigo){
        $fila = $this->consultarUno('select id_persona from personas where person_codigo = ? limit 1', [$persona_codigo]);
        return $fila->id_persona ?? 0;
    }
    //Registrar/actualizar persona (update si trae id_persona, insert si no). Devuelve 1 ok / 2 error.
    public function guardar_persona($model){
        $fecha_actual = date('Y-m-d H:i:s');
        if(isset($model->id_persona)){
            $ok = $this->ejecutar(
                'update personas set persona_nombre = ?, persona_apellido_paterno = ?, persona_apellido_materno = ?, persona_nacimiento = ?, persona_telefono = ?, persona_modificacion = ? where id_persona = ?',
                [$model->persona_nombre, $model->persona_apellido_paterno, $model->persona_apellido_materno, $model->persona_nacimiento, $model->persona_telefono, $fecha_actual, $model->id_persona]
            );
        } else {
            $ok = $this->ejecutar(
                'insert into personas (persona_nombre, persona_apellido_paterno, persona_apellido_materno, persona_nacimiento, persona_telefono, persona_creacion, persona_modificacion, person_codigo) values (?,?,?,?,?,?,?,?)',
                [$model->persona_nombre, $model->persona_apellido_paterno, $model->persona_apellido_materno, $model->persona_nacimiento, $model->persona_telefono, $fecha_actual, $fecha_actual, $model->person_codigo]
            );
        }
        return $ok ? ResultCode::OK : ResultCode::ERROR;
    }
    //Registrar/actualizar usuario (update si trae id_usuario, insert si no). Devuelve 1 ok / 2 error.
    public function guardar_usuario($model){
        $fecha_actual = date('Y-m-d H:i:s');
        if(isset($model->id_usuario)){
            $ok = $this->ejecutar(
                'update usuarios set usuario_nickname = ?, usuario_email = ?, usuario_imagen = ?, usuario_estado = ?, id_rol = ?, usuario_ultima_modificacion = ? where id_usuario = ?',
                [$model->usuario_nickname, $model->usuario_email, $model->usuario_imagen, $model->usuario_estado, $model->id_rol, $fecha_actual, $model->id_usuario]
            );
        } else {
            $ok = $this->ejecutar(
                'insert into usuarios (id_persona, id_rol, usuario_nickname, usuario_contrasenha, usuario_email, usuario_imagen, usuario_estado, usuario_creacion, usuario_ultimo_login, usuario_ultima_modificacion) values (?,?,?,?,?,?,?,?,?,?)',
                [$model->id_persona, $model->id_rol, $model->usuario_nickname, $model->usuario_contrasenha, $model->usuario_email, $model->usuario_imagen, $model->usuario_estado, $fecha_actual, $fecha_actual, $fecha_actual]
            );
        }
        return $ok ? ResultCode::OK : ResultCode::ERROR;
    }
    //Funcion para guardar una nueva contraseña. Devuelve 1 ok / 2 error.
    public function guardar_contrasenha($id_usuario, $contrasenha){
        $ok = $this->ejecutar('update usuarios set usuario_contrasenha = ? where id_usuario = ?', [$contrasenha, $id_usuario]);
        return $ok ? ResultCode::OK : ResultCode::ERROR;
    }
}
