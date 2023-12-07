<?php

declare(strict_types=1);

namespace MezzioTest\Plates;

use League\Plates\Engine;
use League\Plates\Engine as PlatesEngine;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Plates\PlatesEngineFactory;
use Mezzio\Plates\PlatesRenderer;
use Mezzio\Plates\PlatesRendererFactory;
use Mezzio\Template\TemplatePath;
use MezzioTest\Plates\TestAsset\DummyPsrContainer;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

use function sprintf;

final class PlatesRendererFactoryTest extends TestCase
{
    private DummyPsrContainer $container;

    public function setUp(): void
    {
        $this->container = new DummyPsrContainer();
    }

    public function configureEngineService(): void
    {
        $engineFactory = new PlatesEngineFactory();

        $this->container->services[UrlHelper::class]       = $this->createMock(UrlHelper::class);
        $this->container->services[ServerUrlHelper::class] = $this->createMock(ServerUrlHelper::class);
        $this->container->services[PlatesEngine::class]    = $engineFactory($this->container);
    }

    public function fetchPlatesEngine(PlatesRenderer $plates): Engine
    {
        $r = new ReflectionProperty($plates, 'template');
        $r->setAccessible(true);
        return $r->getValue($plates);
    }

    public function getConfigurationPaths(): array
    {
        return [
            'foo' => __DIR__ . '/TestAsset/bar',
            1     => __DIR__ . '/TestAsset/one',
            'bar' => [
                __DIR__ . '/TestAsset/baz',
                __DIR__ . '/TestAsset/bat',
            ],
            0     => [
                __DIR__ . '/TestAsset/two',
                __DIR__ . '/TestAsset/three',
            ],
        ];
    }

    public function assertPathsHasNamespace(?string $namespace, array $paths, ?string $message = null): void
    {
        $message = $message ?: sprintf('Paths do not contain namespace %s', $namespace ?: 'null');

        $found = false;
        foreach ($paths as $path) {
            $this->assertInstanceOf(TemplatePath::class, $path, 'Non-TemplatePath found in paths list');
            if ($path->getNamespace() === $namespace) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, $message);
    }

    public function assertPathNamespaceCount(
        int $expected,
        ?string $namespace,
        array $paths,
        ?string $message = null
    ): void {
        $message = $message ?: sprintf('Did not find %d paths with namespace %s', $expected, $namespace ?: 'null');

        $count = 0;
        foreach ($paths as $path) {
            $this->assertInstanceOf(TemplatePath::class, $path, 'Non-TemplatePath found in paths list');
            if ($path->getNamespace() === $namespace) {
                $count += 1;
            }
        }
        $this->assertSame($expected, $count, $message);
    }

    public function assertPathNamespaceContains(
        string $expected,
        ?string $namespace,
        array $paths,
        ?string $message = null
    ): void {
        $message = $message ?: sprintf('Did not find path %s in namespace %s', $expected, $namespace ?: null);

        $found = [];
        foreach ($paths as $path) {
            $this->assertInstanceOf(TemplatePath::class, $path, 'Non-TemplatePath found in paths list');
            if ($path->getNamespace() === $namespace) {
                $found[] = $path->getPath();
            }
        }
        $this->assertContains($expected, $found, $message);
    }

    public function testCallingFactoryWithNoConfigReturnsPlatesInstance(): PlatesRenderer
    {
        $this->configureEngineService();
        $factory = new PlatesRendererFactory();
        $plates  = $factory($this->container);
        $this->assertInstanceOf(PlatesRenderer::class, $plates);
        return $plates;
    }

    /**
     * @depends testCallingFactoryWithNoConfigReturnsPlatesInstance
     */
    public function testUnconfiguredPlatesInstanceContainsNoPaths(PlatesRenderer $plates): void
    {
        $paths = $plates->getPaths();
        $this->assertIsArray($paths);
        $this->assertEmpty($paths);
    }

    public function testConfiguresTemplateSuffix(): void
    {
        $this->container->services['config'] = [
            'templates' => [
                'extension' => 'html',
            ],
        ];

        $this->configureEngineService();
        $factory = new PlatesRendererFactory();
        $plates  = $factory($this->container);

        $engine = $this->fetchPlatesEngine($plates);

        $this->assertEquals('html', $engine->getFileExtension());
    }

    public function testConfiguresPaths(): void
    {
        $this->container->services['config'] = [
            'templates' => [
                'paths' => [
                    'foo' => __DIR__ . '/TestAsset/bar',
                    1     => __DIR__ . '/TestAsset/one',
                    'bar' => __DIR__ . '/TestAsset/baz',
                ],
            ],
        ];

        $this->configureEngineService();
        $factory = new PlatesRendererFactory();
        $plates  = $factory($this->container);

        $paths = $plates->getPaths();
        $this->assertPathsHasNamespace('foo', $paths);
        $this->assertPathsHasNamespace('bar', $paths);
        $this->assertPathsHasNamespace(null, $paths);

        $this->assertPathNamespaceCount(1, 'foo', $paths);
        $this->assertPathNamespaceCount(1, 'bar', $paths);
        $this->assertPathNamespaceCount(1, null, $paths);

        $this->assertPathNamespaceContains(__DIR__ . '/TestAsset/bar', 'foo', $paths);
        $this->assertPathNamespaceContains(__DIR__ . '/TestAsset/baz', 'bar', $paths);
        $this->assertPathNamespaceContains(__DIR__ . '/TestAsset/one', null, $paths);
    }

    public function testWillPullPlatesEngineFromContainerIfPresent(): void
    {
        $engine = $this->createMock(PlatesEngine::class);

        $this->container->services[PlatesEngine::class] = $engine;

        $factory  = new PlatesRendererFactory();
        $renderer = $factory($this->container);

        $class    = new ReflectionClass($renderer);
        $property = $class->getProperty('template');
        $property->setAccessible(true);
        $template = $property->getValue($renderer);
        $this->assertSame($engine, $template);
    }
}
