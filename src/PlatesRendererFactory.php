<?php

declare(strict_types=1);

namespace Mezzio\Plates;

use League\Plates\Engine as PlatesEngine;
use Psr\Container\ContainerInterface;

use function is_array;
use function is_numeric;

/**
 * Create and return a Plates template instance.
 *
 * Optionally uses the service 'config', which should return an array. This
 * factory consumes the following structure:
 *
 * <code>
 * 'templates' => [
 *     'extension' => 'file extension used by templates; defaults to html',
 *     'paths' => [
 *         // namespace / path pairs
 *         //
 *         // Numeric namespaces imply the default/main namespace. Paths may be
 *         // strings or arrays of string paths to associate with the namespace.
 *     ],
 * ]
 * </code>
 *
 * If the service League\Plates\Engine exists, that value will be used
 * for the PlatesEngine; otherwise, this factory invokes the PlatesEngineFactory
 * to create an instance.
 */
class PlatesRendererFactory
{
    public function __invoke(ContainerInterface $container): PlatesRenderer
    {
        $engine = $this->getEngine($container);

        return new PlatesRenderer($engine);
    }

    /**
     * Create and return a Plates Engine instance.
     *
     * If the container has the League\Plates\Engine service, returns it.
     *
     * Otherwise, invokes the PlatesEngineFactory with the $container to create
     * and return the instance.
     */
    private function getEngine(ContainerInterface $container): PlatesEngine
    {
        if ($container->has(PlatesEngine::class)) {
            return $container->get(PlatesEngine::class);
        }

        trigger_error(sprintf(
            '%s now expects you to register the factory %s for the service %s; '
            . 'please update your dependency configuration.',
            self::class,
            PlatesEngineFactory::class,
            PlatesEngine::class
        ), E_USER_DEPRECATED);

        $factory = new PlatesEngineFactory();
        return $factory($container);
    }
}
