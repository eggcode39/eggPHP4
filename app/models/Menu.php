<?php
/**
 * Created by PhpStorm
 * User: CESARJOSE39
 * Date: 19/10/2020
 * Time: 20:01
 */
class Menu extends BaseModel{
    //Campos usados cuando esta clase actúa como contenedor de datos (DTO) en los controladores.
    //Quedan en null hasta que se asignan; un id en null indica "registro nuevo" en los guardar_*.
    //--- menu ---
    public $id_menu;
    public $menu_nombre;
    public $menu_controlador;
    public $menu_icono;
    public $menu_orden;
    public $menu_mostrar;
    public $menu_estado;
    //--- opcion ---
    public $id_opcion;
    public $opcion_nombre;
    public $opcion_funcion;
    public $opcion_icono;
    public $opcion_mostrar;
    public $opcion_orden;
    public $opcion_estado;
    //--- permiso ---
    public $id_permiso;
    public $permiso_accion;
    public $permiso_estado;
    //El constructor (pdo + log) y los ayudantes consultar/consultarUno/ejecutar vienen de BaseModel.

    //Listamos todos los menus creados en el sistema
    public function listar_menus(){
        return $this->consultar('select * from menus');
    }
    //Listamos los datos del menú según el id enviado
    public function listar_menu($id_menu){
        return $this->consultarUno('select * from menus where id_menu = ?', [$id_menu]);
    }
    //Listamos las opciones de un menú en especifico
    public function listar_opciones($id_menu){
        return $this->consultar('select * from opciones where id_menu = ?', [$id_menu]);
    }
    //Busca si existe un menu con el mismo controlador
    public function buscar_menu_controlador($menu_controlador){
        $fila = $this->consultarUno('select id_menu from menus where menu_controlador = ?', [$menu_controlador]);
        return $fila !== null && isset($fila->id_menu);
    }
    //Busca si existe una opcion repetida en el menu
    public function buscar_opcion_menu($id_menu, $opcion_funcion){
        $fila = $this->consultarUno('select id_opcion from opciones where id_menu = ? and opcion_funcion = ?', [$id_menu, $opcion_funcion]);
        return $fila !== null && isset($fila->id_opcion);
    }
    //Busca si existe un permiso repetido en la opción
    public function buscar_permiso_opcion($id_opcion, $permiso_accion){
        $fila = $this->consultarUno('select id_permiso from permisos where id_opcion = ? and permiso_accion = ?', [$id_opcion, $permiso_accion]);
        return $fila !== null && isset($fila->id_permiso);
    }
    //Busca la relacion entre el rol y el menu
    public function buscar_relacion_rol_menu($id_rol, $id_menu){
        $fila = $this->consultarUno('select id_rol_menu from roles_menus where id_rol = ? and id_menu = ?', [$id_rol, $id_menu]);
        return $fila !== null && isset($fila->id_rol_menu);
    }
    //Busca la restricción entre la opcion y el rol
    public function buscar_restriccion_rol_opcion($id_rol, $id_opcion){
        $fila = $this->consultarUno('select id_restriccion from restricciones where id_rol = ? and id_opcion = ?', [$id_rol, $id_opcion]);
        return $fila !== null && isset($fila->id_restriccion);
    }
    //Agrega la relacion entre el rol y el menu
    public function agregar_relacion($id_rol, $id_menu){
        return $this->ejecutar('insert into roles_menus (id_rol, id_menu) values (?,?)', [$id_rol, $id_menu]) ? ResultCode::OK : ResultCode::ERROR;
    }
    //Eliminar la relacion entre el rol y el menu
    public function eliminar_relacion($id_rol, $id_menu){
        return $this->ejecutar('delete from roles_menus where id_rol = ? and id_menu = ?', [$id_rol, $id_menu]) ? ResultCode::OK : ResultCode::ERROR;
    }
    //Agrega la restriccion entre el rol y la opcion
    public function agregar_restriccion($id_rol, $id_opcion){
        return $this->ejecutar('insert into restricciones (id_rol, id_opcion) values (?,?)', [$id_rol, $id_opcion]) ? ResultCode::OK : ResultCode::ERROR;
    }
    //Eliminar la restriccion entre el rol y la opcion
    public function eliminar_restriccion($id_rol, $id_opcion){
        return $this->ejecutar('delete from restricciones where id_rol = ? and id_opcion = ?', [$id_rol, $id_opcion]) ? ResultCode::OK : ResultCode::ERROR;
    }
    //Lista la opcion según el id consultado
    public function listar_opcion($id_opcion){
        return $this->consultarUno('select * from opciones where id_opcion = ?', [$id_opcion]);
    }
    //Lista los permisos creados por opcion
    public function listar_permisos($id_opcion){
        return $this->consultar('select * from permisos where id_opcion = ?', [$id_opcion]);
    }
    //Eliminar permiso
    public function eliminar_permiso($id_permiso){
        return $this->ejecutar('delete from permisos where id_permiso = ?', [$id_permiso]) ? ResultCode::OK : ResultCode::ERROR;
    }
    //Funcion para guardar los menus creados o editarlos
    public function guardar_menu($model){
        if(isset($model->id_menu)){
            $ok = $this->ejecutar(
                'update menus set menu_nombre = ?, menu_controlador = ?, menu_icono = ?, menu_orden = ?, menu_mostrar = ?, menu_estado = ? where id_menu = ?',
                [$model->menu_nombre, $model->menu_controlador, $model->menu_icono, $model->menu_orden, $model->menu_mostrar, $model->menu_estado, $model->id_menu]
            );
        } else {
            $ok = $this->ejecutar(
                'insert into menus (menu_nombre, menu_controlador, menu_icono, menu_orden, menu_mostrar, menu_estado) values (?,?,?,?,?,?)',
                [$model->menu_nombre, $model->menu_controlador, $model->menu_icono, $model->menu_orden, $model->menu_mostrar, $model->menu_estado]
            );
        }
        return $ok ? ResultCode::OK : ResultCode::ERROR;
    }
    //Funcion para guardar las opciones creadas o editarlas
    public function guardar_opcion($model){
        if(isset($model->id_opcion)){
            $ok = $this->ejecutar(
                'update opciones set opcion_nombre = ?, opcion_funcion = ?, opcion_icono = ?, opcion_mostrar = ?, opcion_orden = ?, opcion_estado = ? where id_opcion = ?',
                [$model->opcion_nombre, $model->opcion_funcion, $model->opcion_icono, $model->opcion_mostrar, $model->opcion_orden, $model->opcion_estado, $model->id_opcion]
            );
        } else {
            $ok = $this->ejecutar(
                'insert into opciones (id_menu, opcion_nombre, opcion_funcion, opcion_icono, opcion_mostrar, opcion_orden, opcion_estado) values (?,?,?,?,?,?,?)',
                [$model->id_menu, $model->opcion_nombre, $model->opcion_funcion, $model->opcion_icono, $model->opcion_mostrar, $model->opcion_orden, $model->opcion_estado]
            );
        }
        return $ok ? ResultCode::OK : ResultCode::ERROR;
    }
    //Funcion para guardar los permisos creados o editarlos
    public function guardar_permiso($model){
        if(isset($model->id_permiso)){
            $ok = $this->ejecutar(
                'update permisos set permiso_accion = ?, permiso_estado = ? where id_permiso = ?',
                [$model->permiso_accion, $model->permiso_estado, $model->id_permiso]
            );
        } else {
            $ok = $this->ejecutar(
                'insert into permisos (id_opcion, permiso_accion, permiso_estado) values (?,?,?)',
                [$model->id_opcion, $model->permiso_accion, $model->permiso_estado]
            );
        }
        return $ok ? ResultCode::OK : ResultCode::ERROR;
    }
}
