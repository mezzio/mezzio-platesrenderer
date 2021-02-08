<?php

/**
 * @see       https://github.com/mezzio/mezzio-platesrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-platesrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-platesrenderer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Plates\TestAsset;

use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;

class TestExtension implements ExtensionInterface
{
    /** @var Engine */
    public static $engine;

    public function register(Engine $engine): void
    {
        self::$engine = $engine;
    }
}
