<?php

/**
 * @see       https://github.com/mezzio/mezzio-platesrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-platesrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-platesrenderer/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Plates\Exception;

use Interop\Container\Exception\ContainerException;
use RuntimeException;

class MissingHelperException extends RuntimeException implements
    ExceptionInterface,
    ContainerException
{
}
