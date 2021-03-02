<?php

/**
 * @see       https://github.com/mezzio/mezzio-platesrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-platesrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-platesrenderer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Plates;

use League\Plates\Engine;
use League\Plates\Engine as PlatesEngine;
use LogicException;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Plates\Extension\EscaperExtension;
use Mezzio\Plates\Extension\UrlExtension;
use Mezzio\Plates\PlatesRenderer;
use Mezzio\Plates\PlatesRendererFactory;
use Mezzio\Template\TemplatePath;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ProphecyInterface;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionProperty;

use function restore_error_handler;
use function set_error_handler;
use function sprintf;

use const E_USER_WARNING;

class PlatesRendererFactoryTest extends TestCase
{
    use ProphecyTrait;

    /** @var ContainerInterface|ProphecyInterface */
    private $container;

    /** @var bool */
    private $errorCaught = false;

    public function setUp(): void
    {
        $this->errorCaught = false;
        $this->container   = $this->prophesize(ContainerInterface::class);
    }

    public function configureEngineService()
    {
        $this->container->has(PlatesEngine::class)->willReturn(false);
        $this->container->has(UrlExtension::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Plates\Extension\UrlExtension::class)->willReturn(false);
        $this->container->has(EscaperExtension::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Plates\Extension\EscaperExtension::class)->willReturn(false);
        $this->container->has(UrlHelper::class)->willReturn(true);
        $this->container->has(ServerUrlHelper::class)->willReturn(true);
        $this->container->get(UrlHelper::class)->willReturn($this->prophesize(UrlHelper::class)->reveal());
        $this->container->get(ServerUrlHelper::class)->willReturn($this->prophesize(ServerUrlHelper::class)->reveal());
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

    public function assertPathNamespaceCount(int $expected, ?string $namespace, array $paths, ?string $message = null)
    {
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
        $this->container->has('config')->willReturn(false);
        $this->configureEngineService();
        $factory = new PlatesRendererFactory();
        $plates  = $factory($this->container->reveal());
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
        $config = [
            'templates' => [
                'extension' => 'html',
            ],
        ];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->configureEngineService();
        $factory = new PlatesRendererFactory();
        $plates  = $factory($this->container->reveal());

        $engine = $this->fetchPlatesEngine($plates);

        $this->assertEquals($config['templates']['extension'], $engine->getFileExtension());
    }

    public function testExceptionIsRaisedIfMultiplePathsSpecifyDefaultNamespace(): void
    {
        $config = [
            'templates' => [
                'paths' => [
                    0 => __DIR__ . '/TestAsset/bar',
                    1 => __DIR__ . '/TestAsset/baz',
                ],
            ],
        ];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->configureEngineService();
        $factory = new PlatesRendererFactory();

        set_error_handler(function ($errno, $errstr) {
            $this->errorCaught = true;
        }, E_USER_WARNING);
        $factory($this->container->reveal());
        restore_error_handler();
        $this->assertTrue($this->errorCaught, 'Did not detect duplicate path for default namespace');
    }

    public function testExceptionIsRaisedIfMultiplePathsInSameNamespace(): void
    {
        $config = [
            'templates' => [
                'paths' => [
                    'bar' => [
                        __DIR__ . '/TestAsset/baz',
                        __DIR__ . '/TestAsset/bat',
                    ],
                ],
            ],
        ];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->configureEngineService();
        $factory = new PlatesRendererFactory();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('already being used');
        $factory($this->container->reveal());
    }

    public function testConfiguresPaths(): void
    {
        $config = [
            'templates' => [
                'paths' => [
                    'foo' => __DIR__ . '/TestAsset/bar',
                    1     => __DIR__ . '/TestAsset/one',
                    'bar' => __DIR__ . '/TestAsset/baz',
                ],
            ],
        ];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->configureEngineService();
        $factory = new PlatesRendererFactory();
        $plates  = $factory($this->container->reveal());

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
        $engine = $this->prophesize(PlatesEngine::class);
        $this->container->has(PlatesEngine::class)->willReturn(true);
        $this->container->get(PlatesEngine::class)->willReturn($engine->reveal());

        $this->container->has('config')->willReturn(false);

        $factory  = new PlatesRendererFactory();
        $renderer = $factory($this->container->reveal());

        $class    = new ReflectionClass($renderer);
        $property = $class->getProperty('template');
        $property->setAccessible(true);
        $template = $property->getValue($renderer);
        $this->assertSame($engine->reveal(), $template);
    }
}
