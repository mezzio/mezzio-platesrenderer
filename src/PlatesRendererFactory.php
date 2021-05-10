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
        $config = $container->has('config') ? $container->get('config') : [];
        $config = $config['templates'] ?? [];

        // Create the engine instance:
        $engine = $this->createEngine($container);

        // Set file extension
        if (isset($config['extension'])) {
            $engine->setFileExtension($config['extension']);
        }

        // Inject engine
        $plates = new PlatesRenderer($engine);

        // Add template paths
        $allPaths = isset($config['paths']) && is_array($config['paths']) ? $config['paths'] : [];
        foreach ($allPaths as $namespace => $paths) {
            $namespace = is_numeric($namespace) ? null : $namespace;
            foreach ((array) $paths as $path) {
                $plates->addPath($path, $namespace);
            }
        }

        return $plates;
    }

    /**
     * Create and return a Plates Engine instance.
     *
     * If the container has the League\Plates\Engine service, returns it.
     *
     * Otherwise, invokes the PlatesEngineFactory with the $container to create
     * and return the instance.
     */
    private function createEngine(ContainerInterface $container): PlatesEngine
    {
        if ($container->has(PlatesEngine::class)) {
            return $container->get(PlatesEngine::class);
        }

        $engineFactory = new PlatesEngineFactory();
        return $engineFactory($container);
    }
}
