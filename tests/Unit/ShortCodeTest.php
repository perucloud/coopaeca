<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/app/Helpers/app.php';

final class ShortCodeTest extends TestCase
{
    /**
     * Prueba unitaria documentada: valida que un pedido use prefijo,
     * guion y correlativo de seis digitos con ceros a la izquierda.
     */
    public function testGeneratesPedidoCodeWithSixDigitIdentifier(): void
    {
        $this->assertSame('PED-000007', short_code('PED', 7));
    }

    /**
     * Prueba unitaria documentada: valida el mismo formato para ventas,
     * confirmando que el prefijo recibido se conserva.
     */
    public function testGeneratesVentaCodeWithSixDigitIdentifier(): void
    {
        $this->assertSame('VEN-000042', short_code('VEN', 42));
    }
}
