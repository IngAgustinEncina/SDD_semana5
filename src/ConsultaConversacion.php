<?php

declare(strict_types=1);

final class ConsultaConversacion
{
    public function __construct(private readonly AccesoDatos $accesoDatos)
    {
    }

    public function obtener(): array
    {
        $comentarios = $this->accesoDatos->obtenerComentarios();
        $respuestas = $this->accesoDatos->obtenerRespuestas();
        $respuestasPorComentario = [];

        foreach ($respuestas as $respuesta) {
            $respuestasPorComentario[(int) $respuesta['comentario_id']][] = [
                'id' => (int) $respuesta['id'],
                'texto' => (string) $respuesta['texto'],
                'autor' => (string) $respuesta['autor'],
                'fecha' => (string) $respuesta['fecha_creacion'],
            ];
        }

        $conversacion = [];

        foreach ($comentarios as $comentario) {
            $id = (int) $comentario['id'];

            $conversacion[] = [
                'id' => $id,
                'texto' => (string) $comentario['texto'],
                'autor' => (string) $comentario['autor'],
                'fecha' => (string) $comentario['fecha_creacion'],
                'respuestas' => $respuestasPorComentario[$id] ?? [],
            ];
        }

        return $conversacion;
    }
}
