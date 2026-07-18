<?php
/**
 * Created by PhpStorm
 * User: CESARJOSE39
 * Date: 12/10/2020
 * Time: 17:28
 */
class AdminController extends BaseController{
    //Navbar: sólo se instancia dentro de las vistas que arman el menú lateral
    private $nav;
    //log, encriptar, sesion y validar vienen de BaseController.
    public function __construct()
    {
        parent::__construct();
    }
    //Vistas/Opciones
    //Vista de acceso al panel de inicio
    public function inicio(){
        try{
            //Llamamos a la clase del Navbar, que sólo se usa
            // en funciones para llamar vistas y la instaciamos
            $this->nav = new Navbar();
            $navs = $this->nav->listar_menus($this->encriptar->desencriptar($_SESSION['ru'],_FULL_KEY_));
            //Hacemos el require de los archivos a usar en las vistas
            require _VIEW_PATH_ . 'header.php';
            require _VIEW_PATH_ . 'navbar.php';
            require _VIEW_PATH_ . 'admin/inicio.php';
            require _VIEW_PATH_ . 'footer.php';
        } catch (Throwable $e){
            //En caso de errores insertamos el error generado y redireccionamos a la vista de inicio
            $this->log->insertar($e->getMessage(), get_class($this).'|'.__FUNCTION__);
            echo "<script language=\"javascript\">alert(\"Error Al Mostrar Contenido. Redireccionando Al Inicio\");</script>";
            echo "<script language=\"javascript\">window.location.href=\"". _SERVER_ ."\";</script>";
        }
    }

    public function finalizar_sesion(){
        $this->sesion->finalizar_sesion();
    }
}

