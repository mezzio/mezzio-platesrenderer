<?php

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
