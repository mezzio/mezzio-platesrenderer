<?php

/**
 * @see       https://github.com/mezzio/mezzio-platesrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-platesrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-platesrenderer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Plates\Extension;

use Laminas\Escaper\Escaper;
use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;

class EscaperExtension implements ExtensionInterface
{
    /** @var Escaper */
    private $escaper;

    public function __construct(?string $encoding = null)
    {
        $this->escaper = new Escaper($encoding);
    }

    /**
     * Register functions with the Plates engine.
     *
     * Registers:
     *
     * - escapeHtml($string) : string
     * - escapeHtmlAttr($string) : string
     * - escapeJs($string) : string
     * - escapeCss($string) : string
     * - escapeUrl($string) : string
     */
    public function register(Engine $engine): void
    {
        $engine->registerFunction('escapeHtml', [$this->escaper, 'escapeHtml']);
        $engine->registerFunction('escapeHtmlAttr', [$this->escaper, 'escapeHtmlAttr']);
        $engine->registerFunction('escapeJs', [$this->escaper, 'escapeJs']);
        $engine->registerFunction('escapeCss', [$this->escaper, 'escapeCss']);
        $engine->registerFunction('escapeUrl', [$this->escaper, 'escapeUrl']);
    }
}
