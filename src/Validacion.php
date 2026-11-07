<?php

declare(strict_types=1);

final class Validacion
{
    public const TEXTO_MINIMO = 3;
    public const TEXTO_MAXIMO = 500;
    public const AUTOR_MAXIMO = 60;

    public static function validarPublicacion(string $texto, string $autor): array
    {
        $textoNormalizado = trim($texto);
        $autorLimpio = trim($autor);
        $errores = [];

        $longitudTexto = self::longitud($textoNormalizado);

        if ($longitudTexto === 0) {
            $errores[] = 'El texto no puede estar vacío ni contener solo espacios.';
        } elseif ($longitudTexto < self::TEXTO_MINIMO) {
            $errores[] = 'El texto debe tener al menos ' . self::TEXTO_MINIMO . ' caracteres.';
        } elseif ($longitudTexto > self::TEXTO_MAXIMO) {
            $errores[] = 'El texto no puede superar los ' . self::TEXTO_MAXIMO . ' caracteres.';
        }

        if ($autorLimpio === '') {
            $errores[] = 'El autor no puede estar vacío.';
        } elseif (self::longitud($autorLimpio) > self::AUTOR_MAXIMO) {
            $errores[] = 'El autor no puede superar los ' . self::AUTOR_MAXIMO . ' caracteres.';
        }

        if ($errores !== []) {
            throw new ValidacionException(implode(' ', $errores));
        }

        return [
            'texto' => $texto,
            'autor' => $autorLimpio,
        ];
    }

    private static function longitud(string $valor): int
    {
        return function_exists('mb_strlen') ? mb_strlen($valor, 'UTF-8') : strlen($valor);
    }
}
