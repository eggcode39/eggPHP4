<?php
/**
 * Autocarga de clases — versión escrita a mano (sin Composer).
 *
 * Idea: cuando PHP encuentra `new Usuario()` y la clase NO está cargada,
 * llama a la función que registramos aquí. Nosotros buscamos un archivo
 * llamado igual que la clase (Usuario.php) dentro de estas carpetas.
 * Así desaparecen los `require 'app/models/...'` repartidos por el proyecto.
 *
 * Esto es CONCEPTUALMENTE lo mismo que hace Composer con "classmap".
 * El día que uses Composer, borras este archivo y cambias una línea
 * (ver docs/composer.md). El resto del proyecto no se entera.
 */
spl_autoload_register(function ($clase) {
    // Carpetas donde viven nuestras clases, en orden de búsqueda.
    $carpetas = [
        'app/controllers/',
        'app/models/',
        'core/',
    ];
    foreach ($carpetas as $carpeta) {
        $archivo = __DIR__ . '/../' . $carpeta . $clase . '.php';
        if (file_exists($archivo)) {
            require $archivo;
            return; // ya la encontramos, no seguimos buscando
        }
    }
    // Si no se encontró, no hacemos nada: PHP lanzará su error normal
    // "Class not found", que es justo lo que queremos ver si nos equivocamos.
});
