<?php

declare(strict_types=1);

require_once __DIR__ . '/_comun.php';

requerirMetodo('POST');

$datos = leerEntrada();

if (array_key_exists('respuesta_id', $datos) || array_key_exists('respuesta_padre_id', $datos)) {
    throw new ValidacionException(
        'La conversación admite un solo nivel de respuestas: no se puede responder a una respuesta.'
    );
}

$comentarioId = filter_var($datos['comentario_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if ($comentarioId === false) {
    throw new ValidacionException('El comentario al que se intenta responder no es válido.');
}

$texto = isset($datos['texto']) && is_string($datos['texto']) ? $datos['texto'] : '';
$autor = isset($datos['autor']) && is_string($datos['autor']) ? $datos['autor'] : '';

$publicacion = new PublicacionRespuestas(new AccesoDatos());

responderJson([
    'respuesta' => $publicacion->publicar((int) $comentarioId, $texto, $autor),
], 201);
