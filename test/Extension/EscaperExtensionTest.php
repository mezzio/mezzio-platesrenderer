<?php

declare(strict_types=1);

namespace MezzioTest\Plates\Extension;

use Laminas\Escaper\Escaper;
use League\Plates\Engine;
use Mezzio\Plates\Extension\EscaperExtension;
use PHPUnit\Framework\TestCase;

final class EscaperExtensionTest extends TestCase
{
    public function testRegistersEscaperFunctionsWithEngine(): void
    {
        $extension = new EscaperExtension();

        $engine = $this->createMock(Engine::class);
        $engine->expects(self::exactly(5))
            ->method('registerFunction')
            ->with(
                self::logicalOr(
                    'escapeHtml',
                    'escapeHtmlAttr',
                    'escapeJs',
                    'escapeCss',
                    'escapeUrl',
                ),
                self::callback(function (array $callback): bool {
                    self::assertArrayHasKey(0, $callback);
                    self::assertArrayHasKey(1, $callback);
                    self::assertInstanceOf(Escaper::class, $callback[0]);
                    self::assertContains(
                        $callback[1],
                        [
                            'escapeHtml',
                            'escapeHtmlAttr',
                            'escapeJs',
                            'escapeCss',
                            'escapeUrl',
                        ]
                    );

                    return true;
                })
            );

        $extension->register($engine);
    }
}
