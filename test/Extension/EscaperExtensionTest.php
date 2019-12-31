<?php

namespace MezzioTest\Plates\Extension;

use Laminas\Escaper\Escaper;
use League\Plates\Engine;
use Mezzio\Plates\Extension\EscaperExtension;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class EscaperExtensionTest extends TestCase
{
    public function testRegistersEscaperFunctionsWithEngine()
    {
        $extension = new EscaperExtension();

        $engine = $this->prophesize(Engine::class);
        $engine
            ->registerFunction('escapeHtml', Argument::that(function ($argument) {
                return is_array($argument) && $argument[0] instanceof Escaper && $argument[1] === 'escapeHtml';
            }))->shouldBeCalled();
        $engine
            ->registerFunction('escapeHtmlAttr', Argument::that(function ($argument) {
                return is_array($argument) && $argument[0] instanceof Escaper && $argument[1] === 'escapeHtmlAttr';
            }))->shouldBeCalled();
        $engine
            ->registerFunction('escapeJs', Argument::that(function ($argument) {
                return is_array($argument) && $argument[0] instanceof Escaper && $argument[1] === 'escapeJs';
            }))->shouldBeCalled();
        $engine
            ->registerFunction('escapeCss', Argument::that(function ($argument) {
                return is_array($argument) && $argument[0] instanceof Escaper && $argument[1] === 'escapeCss';
            }))->shouldBeCalled();
        $engine
            ->registerFunction('escapeUrl', Argument::that(function ($argument) {
                return is_array($argument) && $argument[0] instanceof Escaper && $argument[1] === 'escapeUrl';
            }))->shouldBeCalled();

        $extension->register($engine->reveal());
    }
}