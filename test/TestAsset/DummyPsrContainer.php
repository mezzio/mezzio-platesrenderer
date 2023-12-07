<?php

declare(strict_types=1);

namespace MezzioTest\Plates\TestAsset;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

use function array_key_exists;

final class DummyPsrContainer implements ContainerInterface
{
    public array $services = [];

    /** @inheritDoc */
    public function get($id): mixed
    {
        return $this->services[$id]
            ?? (static function () {
                throw new class extends RuntimeException implements NotFoundExceptionInterface {
                };
            })();
    }

    /** @inheritDoc */
    public function has($id): bool
    {
        return array_key_exists($id, $this->services);
    }
}
