<?php

declare(strict_types=1);

final class PublicacionComentarios
{
    public function __construct(private readonly AccesoDatos $accesoDatos)
    {
    }

    public function publicar(string $texto, string $autor): array
    {
        $datos = Validacion::validarPublicacion($texto, $autor);
        $fecha = date('Y-m-d H:i:s');
        $id = $this->accesoDatos->insertarComentario($datos['texto'], $datos['autor'], $fecha);

        return [
            'id' => $id,
            'texto' => $datos['texto'],
            'autor' => $datos['autor'],
            'fecha' => $fecha,
        ];
    }
}
