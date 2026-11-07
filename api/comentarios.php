<?php

declare(strict_types=1);

require_once __DIR__ . '/_comun.php';

requerirMetodo('POST');

$datos = leerEntrada();
$texto = isset($datos['texto']) && is_string($datos['texto']) ? $datos['texto'] : '';
$autor = isset($datos['autor']) && is_string($datos['autor']) ? $datos['autor'] : '';

$publicacion = new PublicacionComentarios(new AccesoDatos());

responderJson([
    'comentario' => $publicacion->publicar($texto, $autor),
], 201);
