<?php

/**
 * @see       https://github.com/mezzio/mezzio-platesrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-platesrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-platesrenderer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Plates\Extension;

use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Plates\Exception\MissingHelperException;
use Psr\Container\ContainerInterface;

use function sprintf;

/**
 * Factory for creating a UrlExtension instance.
 */
class UrlExtensionFactory
{
    /**
     * @throws MissingHelperException If UrlHelper service is missing.
     * @throws MissingHelperException If ServerUrlHelper service is missing.
     */
    public function __invoke(ContainerInterface $container): UrlExtension
    {
        if (
            ! $container->has(UrlHelper::class)
            && ! $container->has(\Mezzio\Helper\UrlHelper::class)
        ) {
            throw new MissingHelperException(sprintf(
                '%s requires that the %s service be present; not found',
                UrlExtension::class,
                UrlHelper::class
            ));
        }

        if (
            ! $container->has(ServerUrlHelper::class)
            && ! $container->has(\Mezzio\Helper\ServerUrlHelper::class)
        ) {
            throw new MissingHelperException(sprintf(
                '%s requires that the %s service be present; not found',
                UrlExtension::class,
                ServerUrlHelper::class
            ));
        }

        return new UrlExtension(
            $container->has(UrlHelper::class)
                ? $container->get(UrlHelper::class)
                : $container->get(\Mezzio\Helper\UrlHelper::class),
            $container->has(ServerUrlHelper::class)
                ? $container->get(ServerUrlHelper::class)
                : $container->get(\Mezzio\Helper\ServerUrlHelper::class)
        );
    }
}
