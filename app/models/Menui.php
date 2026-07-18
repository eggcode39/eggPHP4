<?php
/**
 * Created by PhpStorm
 * User: CESARJOSE39
 * Date: 10/10/2020
 * Time: 0:38
 */
class Menui extends BaseModel{
    //El constructor (pdo + log) y los ayudantes consultar/consultarUno/ejecutar vienen de BaseModel.

    //Consultamos si el rol especificado tiene permisos para acceder al menu(controlador) y opcion consultada
    public function verificar_permiso_usuario($id_rol, $controlador, $opcion){
        //1 = habilitado, 0 = deshabilitado
        //Validamos la opcion y el rol según el controlador
        $result = $this->consultarUno(
            "select m.menu_estado, o.id_opcion, o.opcion_estado from roles r inner join roles_menus rl on r.id_rol = rl.id_rol inner join menus m on rl.id_menu = m.id_menu inner join opciones o on m.id_menu = o.id_menu where rl.id_rol = ? and m.menu_controlador = ? and o.opcion_funcion = ? and m.menu_estado = 1 and o.opcion_estado = 1 and r.rol_estado = 1 limit 1",
            [$id_rol, $controlador, $opcion]
        );
        if(isset($result->opcion_estado) && $result->opcion_estado == 1){
            //Validamos si hay restriccion de acceso para el rol
            $resultr = $this->consultarUno(
                "select id_restriccion from restricciones where id_rol = ? and id_opcion = ?",
                [$id_rol, $result->id_opcion]
            );
            //Si no esta declarado id_restriccion, quiere decir que no hay restricción para el usuario
            if($resultr === null || !isset($resultr->id_restriccion)){
                return 1;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }
    //Consultamos si el rol especificado tiene permisos para acceder al menu(controlador) y permiso
    public function verificar_permiso_usuario_api($id_rol, $controlador, $permiso){
        $result = $this->consultar(
            "select m.menu_estado, o.opcion_estado, o.id_opcion, p.permiso_estado from roles r inner join roles_menus rl on r.id_rol = rl.id_rol inner join menus m on rl.id_menu = m.id_menu inner join opciones o on m.id_menu = o.id_menu inner join permisos p on o.id_opcion = p.id_opcion where rl.id_rol = ? and m.menu_controlador = ? and p.permiso_accion = ? and m.menu_estado = 1 and o.opcion_estado = 1 and p.permiso_estado = 1 and r.rol_estado = 1",
            [$id_rol, $controlador, $permiso]
        );
        //Si $result es mayor a 0, quiere decir que hay opciones con acceso a verificar
        if(count($result) > 0) {
            //Listamos las restricciones por rol
            $restricciones = $this->listar_restricciones($id_rol);
            $bloqueos = 0;
            //Si el rol tiene restricciones de opciones, procedemos a evaluar cada restriccion con cada opcion
            foreach ($restricciones as $r){
                foreach ($result as $m){
                    //Si la opcion y restriccion coinciden en alguna de las busquedas, se aumenta la variable de $bloqueos
                    if($m->id_opcion == $r->id_opcion){
                        $bloqueos++;
                    }
                }
            }
            //Si $bloqueos es igual a la cantidad de opciones, quiere decir que el rol del usuario no puede acceder
            //a ese permiso y por lo tanto se le niega la función
            if($bloqueos >= count($result)){
                return 0;
            } else {
                return 1;
            }
        } else {
            return 0;
        }
    }
    //Lista las restricciones de rol por usuario
    public function listar_restricciones($id_rol){
        return $this->consultar("select id_opcion from restricciones where id_rol = ?", [$id_rol]);
    }
}
