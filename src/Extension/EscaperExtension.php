<?php

/**
 * @see       https://github.com/mezzio/mezzio-platesrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-platesrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-platesrenderer/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Plates\Extension;

use Laminas\Escaper\Escaper;
use Laminas\Escaper\Exception\InvalidArgumentException;
use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;

class EscaperExtension implements ExtensionInterface
{
    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * EscaperExtension constructor.
     *
     * @param null|string $encoding
     * @throws InvalidArgumentException
     */
    public function __construct($encoding = null)
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
     *
     * @param Engine $engine
     * @return void
     */
    public function register(Engine $engine)
    {
        $engine->registerFunction('escapeHtml', [$this->escaper, 'escapeHtml']);
        $engine->registerFunction('escapeHtmlAttr', [$this->escaper, 'escapeHtmlAttr']);
        $engine->registerFunction('escapeJs', [$this->escaper, 'escapeJs']);
        $engine->registerFunction('escapeCss', [$this->escaper, 'escapeCss']);
        $engine->registerFunction('escapeUrl', [$this->escaper, 'escapeUrl']);
    }
}
