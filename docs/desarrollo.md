# Guía de desarrollo

Cómo está organizado el código y cómo agregar cosas sin romper las convenciones.

## Convenciones de nombres

**Base de datos** (heredado del diseño original):
- Tablas en **plural**, en minúsculas (`usuarios`, `menus`).
- Campos con prefijo del singular de la tabla: `usuario_nickname`, `menu_nombre`.
- Excepciones: la clave primaria `id_<tabla_singular>` y las foráneas `id_<otra_tabla>`.
- Motor **InnoDB**, codificación **`utf8_unicode_ci`**.

**Controladores** (`app/controllers/`):
- Se llaman `<Nombre>Controller.php` y la clase `<Nombre>Controller`.
- Sus métodos se dividen en dos tipos:
  - **Vistas**: renderizan HTML (`require` de vistas). Se llaman por GET desde `index.php`.
  - **Funciones**: devuelven **JSON** con `responder(...)`. Se llaman por POST desde `api.php`.

**Modelos** (`app/models/`):
- Nombre del concepto que representan (`Usuario`, `Menu`). Los que tocan BD extienden `BaseModel`.

**Vistas** (`app/view/`):
- En una carpeta por controlador, con el nombre de la función (`usuario/inicio.php`).

## Autoloader

No hay `require` manuales de clases. `core/autoload.php` busca la clase por nombre
en `app/controllers/`, `app/models/` y `core/`. Si creás una clase `Factura` en
`app/models/Factura.php`, `new Factura()` la carga sola.

## Clase base de modelos — `BaseModel`

Los modelos de BD extienden `BaseModel`, que aporta la conexión (`$pdo`), el `$log`
y tres ayudantes que absorben el `try/catch` repetido:

```php
class Factura extends BaseModel {
    public function listar() {
        return $this->consultar('select * from facturas');          // varias filas -> array
    }
    public function buscar($id) {
        return $this->consultarUno('select * from facturas where id_factura = ?', [$id]); // una fila -> objeto|null
    }
    public function guardar($model) {
        $ok = $this->ejecutar('insert into facturas (total) values (?)', [$model->total]); // insert/update/delete -> bool
        return $ok ? ResultCode::OK : ResultCode::ERROR;
    }
}
```

- `consultar($sql, $params)` → array de filas (`[]` si falla).
- `consultarUno($sql, $params)` → objeto fila o `null`.
- `ejecutar($sql, $params)` → `true`/`false`.
- **Siempre** con consultas preparadas (`?` + array de parámetros). Nunca concatenar entrada del usuario en el SQL.

## Clase base de controladores — `BaseController`

Los controladores extienden `BaseController`, que crea en su constructor las
dependencias comunes (`$log`, `$encriptar`, `$sesion`, `$validar`) y ofrece
`responder()` para la respuesta JSON estándar.

```php
class FacturaController extends BaseController {
    private $factura;
    public function __construct() {
        parent::__construct();            // log, encriptar, sesion, validar
        $this->factura = new Factura();
    }
    // función de API (POST) -> JSON
    public function guardar() {
        $ok = $this->validar->validar_parametro('total', 'POST', true, true, 11, 'numero', 0);
        if (!$ok) {
            return $this->responder(ResultCode::DATOS_INVALIDOS, 'Datos inválidos');
        }
        $model = new Factura();
        $model->total = $_POST['total'];
        $result = $this->factura->guardar($model);
        $this->responder($result, 'OK');
    }
}
```

`responder($code, $message, $extra)` imprime `{"result":{"code":..,"message":..,...extra}}`.
El `$extra` (opcional) son campos adicionales dentro de `result` (ej. `['factura' => $f]`).

## Códigos de resultado — `ResultCode`

Usá las constantes de `core/ResultCode.php` en vez de números mágicos:
`OK` (1), `ERROR` (2), `DUPLICADO` (3), `CREDENCIALES_INVALIDAS` (3),
`CORREO_DUPLICADO` (4), `DATOS_INVALIDOS` (6). El cliente recibe el número.

## Validación de entrada — `Validar`

`Validar::validar_parametro($nombre, $origen, $requerido, $ok_previo, $largo_max, $tipo, $subtipo)`
limpia y valida un parámetro de `POST`/`GET`/`FILES`. Tipos: `texto`, `solo_texto`,
`email`, `fecha`, `numero`, y archivos por extensión. Encadenás varias validaciones
pasando el `$ok` anterior; si una falla, la cadena queda en `false`.

## Cómo agregar una funcionalidad nueva (paso a paso)

Ejemplo: un CRUD de "Facturas".

1. **Modelo** `app/models/Factura.php` extendiendo `BaseModel` con sus consultas.
2. **Controlador** `app/controllers/FacturaController.php` extendiendo `BaseController`,
   con métodos de vista (`inicio`) y de API (`guardar`, `eliminar`…).
3. **Vistas** en `app/view/factura/` (ej. `inicio.php`), incluidas desde el método de vista.
4. **JS** `js/factura.js` con las llamadas ajax a `api/factura/guardar`, etc.
   (el token CSRF ya se adjunta solo vía `domain.js`).
5. **Permisos en la BD** (esto es lo que habilita el acceso):
   - Un `menu` con `menu_controlador = 'Factura'`.
   - Una `opcion` con `opcion_funcion = 'inicio'` (la vista) bajo ese menú.
   - Un `permiso` con `permiso_accion = 'guardar'` bajo esa opción (la función de API).
   - Un registro en `roles_menus` para dar el menú a los roles que correspondan.

   Sin esto, el acceso se deniega aunque el código exista (ver el modelo de
   permisos en el [README](README.md)).

## Tests

`tests/humo.php` es una red de humo en PHP plano con una función `chequear()`.
Al agregar lógica no trivial, sumá un chequeo ahí. Se corre con `php tests/humo.php`
y devuelve código de salida `1` si algo falla (útil para CI).
