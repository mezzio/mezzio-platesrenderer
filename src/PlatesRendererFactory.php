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
        /** @var PlatesEngine $engine */
        $engine = $container->get(PlatesEngine::class);

        return new PlatesRenderer($engine);
    }
}
