<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Validacion;
use ValidacionException;

final class ValidacionTest extends TestCase
{
    public function testRechazaTextoDeDosCaracteres(): void
    {
        $this->expectException(ValidacionException::class);
        Validacion::validarPublicacion('ab', 'Ana');
    }

    public function testRechazaTextoDeQuinientosUnoCaracteres(): void
    {
        $this->expectException(ValidacionException::class);
        Validacion::validarPublicacion(str_repeat('a', 501), 'Ana');
    }

    public function testRechazaTextoSoloEspacios(): void
    {
        $this->expectException(ValidacionException::class);
        Validacion::validarPublicacion('   ', 'Ana');
    }

    public function testRechazaAutorVacio(): void
    {
        $this->expectException(ValidacionException::class);
        Validacion::validarPublicacion('Texto valido', '   ');
    }

    public function testRechazaAutorDeSesentaYUnCaracteres(): void
    {
        $this->expectException(ValidacionException::class);
        Validacion::validarPublicacion('Texto valido', str_repeat('a', 61));
    }

    public function testConservaTextoCrudoyRecortaAutor(): void
    {
        $datos = Validacion::validarPublicacion('  <b>hola</b>  ', '  Ana  ');

        self::assertSame('  <b>hola</b>  ', $datos['texto']);
        self::assertSame('Ana', $datos['autor']);
    }
}
