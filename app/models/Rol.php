<?php
/**
 * Created by PhpStorm
 * User: CESARJOSE39
 * Date: 20/10/2020
 * Time: 17:03
 */
class Rol extends BaseModel{
    //Campos usados cuando esta clase actúa como contenedor de datos (DTO) en los controladores.
    //Quedan en null hasta que se asignan; un id en null indica "registro nuevo" en guardar_rol.
    public $id_rol;
    public $rol_nombre;
    public $rol_descripcion;
    public $rol_estado;
    //El constructor (pdo + log) y los ayudantes consultar/consultarUno/ejecutar vienen de BaseModel.

    //Listamos todos los roles existentes en el sistema
    public function listar_roles(){
        return $this->consultar('select * from roles');
    }
    //Listamos todos los roles existentes en el sistema para superadmin
    public function listar_roles_superadmin(){
        return $this->consultar('select * from roles where id_rol <> 1');
    }
    //Listamos todos los roles existentes en el sistema para admin
    public function listar_roles_usuario(){
        return $this->consultar('select * from roles where id_rol <> 1 and id_rol <> 2');
    }
    //Listamos el rol segun el id recibido
    public function listar_rol($id_rol){
        return $this->consultarUno('select * from roles where id_rol = ?', [$id_rol]);
    }
    //Busca si existe un rol repetido
    public function buscar_rol($rol_nombre){
        $fila = $this->consultarUno('select id_rol from roles where rol_nombre = ?', [$rol_nombre]);
        return $fila !== null && isset($fila->id_rol);
    }
    //Guardamos los cambios en el rol (update si trae id_rol, insert si no). Devuelve 1 ok / 2 error.
    public function guardar_rol($model){
        if(isset($model->id_rol)){
            $ok = $this->ejecutar(
                'update roles set rol_nombre = ?, rol_descripcion = ?, rol_estado = ? where id_rol = ?',
                [$model->rol_nombre, $model->rol_descripcion, $model->rol_estado, $model->id_rol]
            );
        } else {
            $ok = $this->ejecutar(
                'insert into roles (rol_nombre, rol_descripcion, rol_estado) values (?,?,?)',
                [$model->rol_nombre, $model->rol_descripcion, $model->rol_estado]
            );
        }
        return $ok ? ResultCode::OK : ResultCode::ERROR;
    }
}
