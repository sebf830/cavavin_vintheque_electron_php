// router.php
<?php

if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . '/public' . $path;

    if (is_file($file)) {
        return false; // Laisse PHP servir le fichier statique
    }
}

require __DIR__ . '/public/index.php';
