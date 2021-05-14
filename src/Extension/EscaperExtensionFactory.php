<?php

declare(strict_types=1);

namespace Mezzio\Plates\Extension;

use Laminas\Escaper\Exception\InvalidArgumentException;
use Psr\Container\ContainerInterface;

/**
 * Factory for creating a EscaperExtension instance.
 *
 * Optionally uses the service 'config', which should return an array. This
 * factory consumes the following structure:
 *
 * <code>
 * 'plates' => [
 *     'encoding' => 'global encoding value, if not set then will fallback to UTF-8'
 * ]
 * </code>
 */
class EscaperExtensionFactory
{
    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(ContainerInterface $container): EscaperExtension
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $config = $config['plates'] ?? [];

        // Create new EscaperExtension instance
        return new EscaperExtension($config['encoding'] ?? null);
    }
}
