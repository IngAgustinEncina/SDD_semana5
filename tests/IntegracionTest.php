<?php

declare(strict_types=1);

namespace Tests;

use AccesoDatos;
use PHPUnit\Framework\TestCase;
use PublicacionComentarios;
use PublicacionRespuestas;
use ValidacionException;

final class IntegracionTest extends TestCase
{
    private AccesoDatos $acceso;

    protected function setUp(): void
    {
        $this->acceso = new AccesoDatos();
    }

    public function testLaFechaLaAsignaElServidorYElTextoSeGuardaCrudo(): void
    {
        $antes = date('Y-m-d H:i:s');
        $resultado = (new PublicacionComentarios($this->acceso))->publicar('<b>hola</b>', 'Ana');
        $despues = date('Y-m-d H:i:s');

        self::assertSame('<b>hola</b>', $resultado['texto']);
        self::assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $resultado['fecha']
        );
        self::assertGreaterThanOrEqual($antes, $resultado['fecha']);
        self::assertLessThanOrEqual($despues, $resultado['fecha']);

        $guardado = self::buscarPorId($this->acceso->obtenerComentarios(), $resultado['id']);
        self::assertSame('<b>hola</b>', $guardado['texto']);
        self::assertSame($resultado['fecha'], $guardado['fecha_creacion']);
    }

    public function testLosComentariosSeOrdenanPorFechaYIdDescendente(): void
    {
        $fecha = '2024-01-01 00:00:00';
        $primero = $this->acceso->insertarComentario('Primero', 'Ana', $fecha);
        $segundo = $this->acceso->insertarComentario('Segundo', 'Ana', $fecha);
        $tercero = $this->acceso->insertarComentario('Tercero', 'Ana', $fecha);

        $ids = array_map('intval', array_column($this->acceso->obtenerComentarios(), 'id'));
        $posiciones = [];

        foreach ([$tercero, $segundo, $primero] as $id) {
            $posicion = array_search($id, $ids, true);
            self::assertIsInt($posicion);
            $posiciones[] = $posicion;
        }

        self::assertLessThan($posiciones[1], $posiciones[0]);
        self::assertLessThan($posiciones[2], $posiciones[1]);
    }

    public function testLasRespuestasSeOrdenanPorFechaYIdAscendente(): void
    {
        $comentario = $this->acceso->insertarComentario('Padre', 'Ana', '2024-02-01 00:00:00');
        $antigua = $this->acceso->insertarRespuesta($comentario, 'Antigua', 'B', '2024-02-01 00:00:01');
        $empate = $this->acceso->insertarRespuesta($comentario, 'Empate', 'B', '2024-02-01 00:00:01');
        $nueva = $this->acceso->insertarRespuesta($comentario, 'Nueva', 'B', '2024-02-01 00:00:02');

        $respuestas = array_filter(
            $this->acceso->obtenerRespuestas(),
            static fn (array $respuesta): bool => (int) $respuesta['comentario_id'] === $comentario
        );

        self::assertSame(
            [$antigua, $empate, $nueva],
            array_map('intval', array_column($respuestas, 'id'))
        );
    }

    public function testRechazaResponderARespuesta(): void
    {
        $comentario = $this->acceso->insertarComentario('Padre', 'Ana', '2024-03-01 00:00:00');
        $maximoComentarioId = 0;

        foreach ($this->acceso->obtenerComentarios() as $fila) {
            $maximoComentarioId = max($maximoComentarioId, (int) $fila['id']);
        }

        $publicador = new PublicacionRespuestas($this->acceso);
        $respuesta = $publicador->publicar($comentario, 'Una respuesta', 'B');

        while ($respuesta['id'] <= $maximoComentarioId) {
            $respuesta = $publicador->publicar($comentario, 'Otra respuesta', 'B');
        }

        $this->expectException(ValidacionException::class);
        $publicador->publicar($respuesta['id'], 'Respuesta anidada', 'C');
    }

    private static function buscarPorId(array $filas, int $id): array
    {
        foreach ($filas as $fila) {
            if ((int) $fila['id'] === $id) {
                return $fila;
            }
        }

        self::fail('No se encontro el registro con id ' . $id);
    }
}
