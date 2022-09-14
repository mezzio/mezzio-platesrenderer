<?php

declare(strict_types=1);

namespace MezzioTest\Plates\Extension;

use Laminas\Escaper\Escaper;
use League\Plates\Engine;
use Mezzio\Plates\Extension\EscaperExtension;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

use function is_array;

class EscaperExtensionTest extends TestCase
{
    use ProphecyTrait;

    public function testRegistersEscaperFunctionsWithEngine(): void
    {
        $extension = new EscaperExtension();

        $engine = $this->prophesize(Engine::class);
        $engine
            ->registerFunction('escapeHtml', Argument::that(
                static fn($argument) => is_array($argument) &&
                    $argument[0] instanceof Escaper && $argument[1] === 'escapeHtml'
            ))->shouldBeCalled();
        $engine
            ->registerFunction('escapeHtmlAttr', Argument::that(
                static fn($argument) => is_array($argument) &&
                    $argument[0] instanceof Escaper && $argument[1] === 'escapeHtmlAttr'
            ))->shouldBeCalled();
        $engine
            ->registerFunction('escapeJs', Argument::that(
                static fn($argument) => is_array($argument) &&
                    $argument[0] instanceof Escaper && $argument[1] === 'escapeJs'
            ))->shouldBeCalled();
        $engine
            ->registerFunction('escapeCss', Argument::that(
                static fn($argument) => is_array($argument) &&
                    $argument[0] instanceof Escaper && $argument[1] === 'escapeCss'
            ))->shouldBeCalled();
        $engine
            ->registerFunction('escapeUrl', Argument::that(
                static fn($argument) => is_array($argument) &&
                    $argument[0] instanceof Escaper && $argument[1] === 'escapeUrl'
            ))->shouldBeCalled();

        $extension->register($engine->reveal());
    }
}
