<?php

declare(strict_types=1);

final class PublicacionRespuestas
{
    public function __construct(private readonly AccesoDatos $accesoDatos)
    {
    }

    public function publicar(int $comentarioId, string $texto, string $autor): array
    {
        if (!$this->accesoDatos->existeComentario($comentarioId)) {
            throw new ValidacionException('El comentario al que se intenta responder no existe.');
        }

        $datos = Validacion::validarPublicacion($texto, $autor);
        $fecha = date('Y-m-d H:i:s');
        $id = $this->accesoDatos->insertarRespuesta($comentarioId, $datos['texto'], $datos['autor'], $fecha);

        return [
            'id' => $id,
            'comentario_id' => $comentarioId,
            'texto' => $datos['texto'],
            'autor' => $datos['autor'],
            'fecha' => $fecha,
        ];
    }
}
