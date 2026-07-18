<?php
/**
 * Created by PhpStorm
 * User: CESARJOSE39
 * Date: 10/10/2020
 * Time: 0:45
 */
class Navbar extends BaseModel{
    //El constructor (pdo + log) y los ayudantes consultar/consultarUno/ejecutar vienen de BaseModel.

    //Listar Los Menus Disponibles por Rol
    public function listar_menus($id_rol){
        return $this->consultar(
            "select m.id_menu, m.menu_nombre, m.menu_controlador, m.menu_icono from roles r inner join roles_menus rm on r.id_rol = rm.id_rol inner join menus m on rm.id_menu = m.id_menu where r.id_rol = ? and m.menu_estado = 1 and m.menu_mostrar = 1 order by m.menu_orden asc",
            [$id_rol]
        );
    }
    //Listar las opciones del menú
    public function listar_opciones($id_menu){
        return $this->consultar(
            "select o.id_opcion, o.opcion_nombre, o.opcion_funcion from menus m inner join opciones o on m.id_menu = o.id_menu where m.id_menu = ? and o.opcion_estado = 1 and o.opcion_mostrar = 1 order by o.opcion_orden asc",
            [$id_menu]
        );
    }
    //Trae TODAS las opciones de los menús de un rol en UNA sola consulta, agrupadas
    //por id_menu. Reemplaza llamar a listar_opciones() dentro del loop del navbar
    //(que generaba N+1 consultas al pintar el menú lateral en cada request).
    //Devuelve: [id_menu => [opcion, opcion, ...], ...]
    public function listar_opciones_por_rol($id_rol){
        $filas = $this->consultar(
            "select o.id_menu, o.id_opcion, o.opcion_nombre, o.opcion_funcion from roles_menus rm inner join opciones o on rm.id_menu = o.id_menu where rm.id_rol = ? and o.opcion_estado = 1 and o.opcion_mostrar = 1 order by o.opcion_orden asc",
            [$id_rol]
        );
        $agrupado = [];
        foreach($filas as $f){
            $agrupado[$f->id_menu][] = $f;
        }
        return $agrupado;
    }
    //Lista las restricciones de rol por usuario
    public function listar_restricciones($id_rol){
        return $this->consultar("select id_opcion from restricciones where id_rol = ?", [$id_rol]);
    }
    //Listar el Nombre de la Funcion
    public function listar_nombre_opcion($menu_nombre, $opcion_funcion){
        return $this->consultarUno(
            "select o.opcion_nombre from menus m inner join opciones o on m.id_menu = o.id_menu where m.menu_nombre = ? and o.opcion_funcion = ? limit 1",
            [$menu_nombre, $opcion_funcion]
        );
    }
}
