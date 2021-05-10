<?php

declare(strict_types=1);

namespace Mezzio\Plates\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

class MissingHelperException extends RuntimeException implements
    ExceptionInterface,
    ContainerExceptionInterface
{
}
