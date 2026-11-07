<?php

declare(strict_types=1);

require_once __DIR__ . '/_comun.php';

requerirMetodo('GET');

$consulta = new ConsultaConversacion(new AccesoDatos());

responderJson(['comentarios' => $consulta->obtener()]);
