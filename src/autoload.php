<?php

declare(strict_types=1);

spl_autoload_register(static function (string $clase): void {
    $archivo = __DIR__ . DIRECTORY_SEPARATOR . $clase . '.php';

    if (is_file($archivo)) {
        require $archivo;
    }
});
