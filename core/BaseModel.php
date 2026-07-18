<?php
/**
 * Clase base de los modelos que hablan con la base de datos.
 *
 * Centraliza lo que antes se repetía en CADA modelo:
 *   1) Crear la conexión (pdo) y el log en el constructor.
 *   2) El try/catch alrededor de cada consulta, con su registro en el log.
 *
 * Un modelo la usa con "extends BaseModel" y llama a estos 3 ayudantes:
 *   - consultar()    -> SELECT de varias filas (listas). Devuelve array; [] si falla.
 *   - consultarUno() -> SELECT de una fila.            Devuelve objeto o null.
 *   - ejecutar()     -> INSERT / UPDATE / DELETE.      Devuelve true / false.
 *
 * Si una consulta falla, se anota en el log (clase + el SQL que falló) y se
 * devuelve un valor seguro, igual que hacían los modelos antes a mano.
 */
class BaseModel {
    protected $pdo;
    protected $log;

    public function __construct() {
        $this->pdo = Database::getConnection();
        $this->log = new Log();
    }

    // SELECT de varias filas. Devuelve un array de filas (vacío si no hay o si falla).
    protected function consultar($sql, $params = []) {
        try {
            $stm = $this->pdo->prepare($sql);
            $stm->execute($params);
            return $stm->fetchAll();
        } catch (Throwable $e) {
            $this->log->insertar($e->getMessage(), get_class($this) . ' | ' . $sql);
            return [];
        }
    }

    // SELECT de una sola fila. Devuelve el objeto fila, o null si no hay o si falla.
    protected function consultarUno($sql, $params = []) {
        try {
            $stm = $this->pdo->prepare($sql);
            $stm->execute($params);
            $fila = $stm->fetch();
            return $fila === false ? null : $fila;
        } catch (Throwable $e) {
            $this->log->insertar($e->getMessage(), get_class($this) . ' | ' . $sql);
            return null;
        }
    }

    // INSERT / UPDATE / DELETE. Devuelve true si se ejecutó, false si falló.
    protected function ejecutar($sql, $params = []) {
        try {
            $stm = $this->pdo->prepare($sql);
            $stm->execute($params);
            return true;
        } catch (Throwable $e) {
            $this->log->insertar($e->getMessage(), get_class($this) . ' | ' . $sql);
            return false;
        }
    }
}
