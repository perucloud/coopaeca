<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class TranslationTest extends TestCase
{
    protected function setUp(): void
    {
        $_GET = [];
        $_SESSION = [];
    }

    /**
     * Experimento: al solicitar ingles, el catalogo debe devolver la etiqueta
     * inglesa de navegacion. Evidencia de la hipotesis del informe tecnico.
     */
    public function testReturnsEnglishTranslationWhenLanguageIsRequested(): void
    {
        $_GET['lang'] = 'en';

        self::assertSame('Home', t('nav.home'));
    }

    public function testFallsBackToSpanishForAnUnknownLanguage(): void
    {
        $_GET['lang'] = 'fr';

        self::assertSame('Inicio', t('nav.home'));
    }
}
