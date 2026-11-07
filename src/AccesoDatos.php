<?php

declare(strict_types=1);

final class AccesoDatos
{
    private PDO $conexion;

    public function __construct()
    {
        $config = require __DIR__ . '/../config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        $this->conexion = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public function insertarComentario(string $texto, string $autor, string $fecha): int
    {
        $sentencia = $this->conexion->prepare(
            'INSERT INTO comentarios (texto, autor, fecha_creacion)
             VALUES (:texto, :autor, :fecha)'
        );

        $sentencia->execute([
            ':texto' => $texto,
            ':autor' => $autor,
            ':fecha' => $fecha,
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    public function insertarRespuesta(int $comentarioId, string $texto, string $autor, string $fecha): int
    {
        $sentencia = $this->conexion->prepare(
            'INSERT INTO respuestas (comentario_id, texto, autor, fecha_creacion)
             VALUES (:comentario_id, :texto, :autor, :fecha)'
        );

        $sentencia->execute([
            ':comentario_id' => $comentarioId,
            ':texto' => $texto,
            ':autor' => $autor,
            ':fecha' => $fecha,
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    public function existeComentario(int $comentarioId): bool
    {
        $sentencia = $this->conexion->prepare('SELECT 1 FROM comentarios WHERE id = :id LIMIT 1');
        $sentencia->execute([':id' => $comentarioId]);

        return $sentencia->fetchColumn() !== false;
    }

    public function obtenerComentarios(): array
    {
        $sentencia = $this->conexion->prepare(
            'SELECT id, texto, autor, fecha_creacion
             FROM comentarios
             ORDER BY fecha_creacion DESC, id DESC'
        );
        $sentencia->execute();

        return $sentencia->fetchAll();
    }

    public function obtenerRespuestas(): array
    {
        $sentencia = $this->conexion->prepare(
            'SELECT id, comentario_id, texto, autor, fecha_creacion
             FROM respuestas
             ORDER BY fecha_creacion ASC, id ASC'
        );
        $sentencia->execute();

        return $sentencia->fetchAll();
    }
}
