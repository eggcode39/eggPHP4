<?php
/**
 * Pruebas de humo (smoke tests) SIN framework.
 * Se corren desde la raíz del proyecto con:   php tests/humo.php
 *
 * Qué son: una red de seguridad mínima. Cada chequeo compara un resultado
 * esperado contra el real. Si algo se rompe al refactorizar (autoloader,
 * clases base, etc.), aquí salta en rojo antes de que lo note un usuario.
 *
 * Cómo leerlo: cada linea chequear(condicion, "descripcion") imprime OK o FALLA.
 * Al final muestra el total y devuelve código de salida 1 si hubo fallos.
 */

// --- Bootstrap: cargamos lo necesario a mano.
//     En la Fase 1 (autoloader) estos require se reemplazan por una sola línea. ---
require __DIR__ . '/../core/globals.php';
require __DIR__ . '/../core/autoload.php';
// Con el autoloader ya no hace falta requerir cada clase: se cargan al usarse.

// Contadores globales y la función de chequeo. Todo a la vista, sin magia.
$pruebas = 0;
$fallos  = 0;
function chequear($condicion, $descripcion) {
    global $pruebas, $fallos;
    $pruebas++;
    if ($condicion) {
        echo "  OK   | $descripcion\n";
    } else {
        $fallos++;
        echo " FALLA | $descripcion\n";
    }
}

echo "== Encriptar (ida y vuelta) ==\n";
$enc = new Encriptar();
$clave = 'claveDePrueba';
chequear($enc->desencriptar($enc->encriptar('hola mundo', $clave), $clave) === 'hola mundo',
    'encriptar -> desencriptar devuelve el texto original');
chequear($enc->desencriptar($enc->encriptar('12345', $clave), $clave) === '12345',
    'ida y vuelta con dígitos');
chequear($enc->desencriptar('', $clave) === '',
    'desencriptar cadena vacía devuelve vacío (no revienta)');
chequear($enc->encriptar(null, $clave) === '',
    'encriptar(null) devuelve vacío sin deprecation (campo opcional vacío)');

echo "== Validar ==\n";
$v = new Validar();
chequear($v->validar_email('test@correo.com', true, 60) === true,  'email válido pasa');
chequear($v->validar_email('no-es-email',     true, 60) === false, 'email inválido falla');
chequear($v->validar_numeros('123', true, 11) === true,  'número válido pasa');
chequear($v->validar_numeros('abc', true, 11) === false, 'texto en campo numérico falla');
chequear($v->validar_texto('hola',       true, 10) === true,  'texto dentro del largo pasa');
chequear($v->validar_texto('abcdefghij', true, 5)  === false, 'texto que excede el largo falla');
chequear($v->validar_fechas('2024-01-15', true, 10, 'fecha') === true,  'fecha yyyy-mm-dd válida pasa');
chequear($v->validar_fechas('15/01/2024', true, 10, 'fecha') === false, 'otro formato de fecha falla');
// Bug #1: un campo REQUERIDO ausente no debe pasar; uno OPCIONAL ausente sí.
$_POST = [];
chequear($v->validar_parametro('no_existe', 'POST', true,  true, 10, 'texto', 0) === false,
    'validar_parametro: requerido ausente => inválido');
chequear($v->validar_parametro('no_existe', 'POST', false, true, 10, 'texto', 0) === true,
    'validar_parametro: opcional ausente => válido');
// Nota: el bug de validar_parametro (campo REQUERIDO ausente => devuelve true) NO se cubre
// aquí a propósito: está en la lista para arreglarse (hallazgo #1 de la auditoría de bugs).

echo "== ResultCode (fija los códigos que recibe el cliente) ==\n";
chequear(ResultCode::OK === 1,               'ResultCode::OK vale 1');
chequear(ResultCode::ERROR === 2,            'ResultCode::ERROR vale 2');
chequear(ResultCode::DUPLICADO === 3,        'ResultCode::DUPLICADO vale 3');
chequear(ResultCode::CORREO_DUPLICADO === 4, 'ResultCode::CORREO_DUPLICADO vale 4');
chequear(ResultCode::DATOS_INVALIDOS === 6,  'ResultCode::DATOS_INVALIDOS vale 6');

// --- Base de datos: OPCIONAL. Sólo corre si hay conexión.
//     OJO: Database::getConnection() hace exit() si falla, por eso probamos PDO
//     directo primero para no matar la suite cuando la BD está apagada. ---
echo "== Base de datos (opcional) ==\n";
$hay_db = false;
try {
    new PDO('mysql:host=' . _SERVER_DB_ . ';dbname=' . _DB_ . ';charset=utf8', _USER_DB_, _PASSWORD_DB_);
    $hay_db = true;
} catch (Throwable $e) {
    echo "  --   | BD no disponible, se omiten las pruebas de consulta\n";
}
if ($hay_db) {
    // Una lectura por modelo: prueba que la clase carga y su consulta no revienta.
    // Es la red que protege la migración a BaseModel.
    $rol = new Rol(); // Rol y Database se autocargan
    chequear(is_array((new Usuario())->listar_usuarios()),        'Usuario::listar_usuarios() devuelve un array');
    chequear(is_array((new Menu())->listar_menus()),              'Menu::listar_menus() devuelve un array');
    chequear(is_array((new Navbar())->listar_menus(1)),           'Navbar::listar_menus() devuelve un array');
    // #4: la consulta agrupada (anti N+1) debe dar EXACTAMENTE las mismas opciones que la vieja por-menú.
    $nav_t = new Navbar();
    $agrupado_t = $nav_t->listar_opciones_por_rol(2);
    $coincide_t = true;
    foreach($nav_t->listar_menus(2) as $m_t){
        $viejo_t = array_map(fn($o) => $o->id_opcion, $nav_t->listar_opciones($m_t->id_menu));
        $nuevo_t = array_map(fn($o) => $o->id_opcion, $agrupado_t[$m_t->id_menu] ?? []);
        sort($viejo_t); sort($nuevo_t);
        if($viejo_t !== $nuevo_t){ $coincide_t = false; }
    }
    chequear($coincide_t, '#4: listar_opciones_por_rol coincide con listar_opciones por menú');
    chequear(is_array((new Menui())->listar_restricciones(1)),    'Menui::listar_restricciones() devuelve un array');
    chequear(in_array((new Menui())->verificar_permiso_usuario(1, 'Nada', 'nada'), [0, 1], true),
        'Menui::verificar_permiso_usuario() devuelve 0 ó 1');

    chequear(is_array($rol->listar_roles()), 'Rol::listar_roles() devuelve un array');
    // C4: token opaco crear -> validar (round-trip) y rechazo de uno falso. Se limpia al final.
    $usuarios_c4 = (new Usuario())->listar_usuarios();
    if(count($usuarios_c4) > 0){
        $tk_c4 = new Token();
        $tok_c4 = $tk_c4->crear_token($usuarios_c4[0]->id_usuario);
        chequear($tok_c4 !== false && strlen($tok_c4) === 64, 'C4: crear_token devuelve token de 64 hex');
        chequear($tk_c4->validar_token($tok_c4) === true,        'C4: validar_token acepta el token recién creado');
        chequear($tk_c4->validar_token('token_invalido') === false, 'C4: validar_token rechaza un token inválido');
        $tk_c4->eliminar_token($tok_c4); //limpieza
    }
}

echo "\n----------------------------------------\n";
echo "Pruebas: $pruebas | Fallos: $fallos\n";
exit($fallos === 0 ? 0 : 1);
