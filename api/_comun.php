<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/autoload.php';

ini_set('display_errors', '0');
ini_set('log_errors', '1');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function responderJson(array $datos, int $estado = 200): void
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function requerirMetodo(string $metodo): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $metodo) {
        header('Allow: ' . $metodo);
        responderJson(['error' => 'Método no permitido.'], 405);
    }
}

function leerEntrada(): array
{
    $tipo = $_SERVER['CONTENT_TYPE'] ?? '';

    if (stripos($tipo, 'application/json') !== false) {
        $crudo = file_get_contents('php://input');

        if ($crudo === false || trim($crudo) === '') {
            return [];
        }

        try {
            $datos = json_decode($crudo, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new ValidacionException('El cuerpo de la solicitud no es un JSON válido.');
        }

        if (!is_array($datos)) {
            throw new ValidacionException('El cuerpo de la solicitud debe ser un objeto JSON.');
        }

        return $datos;
    }

    return $_POST;
}

set_exception_handler(static function (Throwable $error): void {
    if ($error instanceof ValidacionException) {
        responderJson(['error' => $error->getMessage()], 422);
    }

    error_log('Error en la API de comentarios: ' . $error->getMessage());

    responderJson(['error' => 'No se pudo procesar la solicitud.'], 500);
});
