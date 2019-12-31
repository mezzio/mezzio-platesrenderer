<?php

/**
 * @see       https://github.com/mezzio/mezzio-platesrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-platesrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-platesrenderer/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Plates;

use Interop\Container\ContainerInterface;
use League\Plates\Engine as PlatesEngine;
use League\Plates\Extension\ExtensionInterface;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Plates\Exception\InvalidExtensionException;
use Mezzio\Plates\Extension\UrlExtension;
use Mezzio\Plates\PlatesEngineFactory;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use stdClass;

class PlatesEngineFactoryTest extends TestCase
{
    public function setUp()
    {
        TestAsset\TestExtension::$engine = null;
        $this->container = $this->prophesize(ContainerInterface::class);

        $this->container->has(UrlHelper::class)->willReturn(true);
        $this->container->get(UrlHelper::class)->willReturn(
            $this->prophesize(UrlHelper::class)->reveal()
        );

        $this->container->has(ServerUrlHelper::class)->willReturn(true);
        $this->container->get(ServerUrlHelper::class)->willReturn(
            $this->prophesize(ServerUrlHelper::class)->reveal()
        );

        $this->container->has(UrlExtension::class)->willReturn(false);

        $this->container->has(\Zend\Expressive\Plates\Extension\UrlExtension::class)->willReturn(false);
    }

    public function testFactoryReturnsPlatesEngine()
    {
        $this->container->has('config')->willReturn(false);
        $factory = new PlatesEngineFactory();
        $engine = $factory($this->container->reveal());
        $this->assertInstanceOf(PlatesEngine::class, $engine);
        return $engine;
    }

    /**
     * @depends testFactoryReturnsPlatesEngine
     */
    public function testUrlExtensionIsRegisteredByDefault($engine)
    {
        $this->assertTrue($engine->doesFunctionExist('url'));
        $this->assertTrue($engine->doesFunctionExist('serverurl'));
    }

    public function testFactoryCanRegisterConfiguredExtensions()
    {
        $extensionOne = $this->prophesize(ExtensionInterface::class);
        $extensionOne->register(Argument::type(PlatesEngine::class))->shouldBeCalled();

        $extensionTwo = $this->prophesize(ExtensionInterface::class);
        $extensionTwo->register(Argument::type(PlatesEngine::class))->shouldBeCalled();
        $this->container->has('ExtensionTwo')->willReturn(true);
        $this->container->get('ExtensionTwo')->willReturn($extensionTwo->reveal());

        $this->container->has(TestAsset\TestExtension::class)->willReturn(false);

        $this->container->has(\ZendTest\Expressive\Plates\TestAsset\TestExtension::class)->willReturn(false);

        $config = [
            'plates' => [
                'extensions' => [
                    $extensionOne->reveal(),
                    'ExtensionTwo',
                    TestAsset\TestExtension::class,
                ],
            ],
        ];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);

        $factory = new PlatesEngineFactory();
        $engine = $factory($this->container->reveal());
        $this->assertInstanceOf(PlatesEngine::class, $engine);

        // Test that the TestExtension was registered. The other two extensions
        // are verified via mocking.
        $this->assertSame($engine, TestAsset\TestExtension::$engine);
    }

    public function invalidExtensions()
    {
        return [
            'null' => [null],
            'true' => [true],
            'false' => [false],
            'zero' => [0],
            'int' => [1],
            'zero-float' => [0.0],
            'float' => [1.1],
            'non-class-string' => ['not-a-class'],
            'array' => [['not-an-extension']],
            'non-extension-object' => [(object) ['extension' => 'not-really']],
        ];
    }

    /**
     * @dataProvider invalidExtensions
     */
    public function testFactoryRaisesExceptionForInvalidExtensions($extension)
    {
        $config = [
            'plates' => [
                'extensions' => [
                    $extension,
                ],
            ],
        ];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);

        if (is_string($extension)) {
            $this->container->has($extension)->willReturn(false);
        }

        $factory = new PlatesEngineFactory();
        $this->setExpectedException(InvalidExtensionException::class);
        $factory($this->container->reveal());
    }

    public function testFactoryRaisesExceptionWhenAttemptingToInjectAnInvalidExtensionService()
    {
        $config = [
            'plates' => [
                'extensions' => [
                    'FooExtension',
                ],
            ],
        ];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);

        $this->container->has('FooExtension')->willReturn(true);
        $this->container->get('FooExtension')->willReturn(new stdClass());

        $factory = new PlatesEngineFactory();
        $this->setExpectedException(InvalidExtensionException::class, 'ExtensionInterface');
        $factory($this->container->reveal());
    }

    public function testFactoryRaisesExceptionWhenNonServiceClassIsAnInvalidExtension()
    {
        $config = [
            'plates' => [
                'extensions' => [
                    stdClass::class,
                ],
            ],
        ];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);

        $this->container->has(stdClass::class)->willReturn(false);

        $this->container->has(\ZendTest\Expressive\Plates\stdClass::class)->willReturn(false);

        $factory = new PlatesEngineFactory();
        $this->setExpectedException(InvalidExtensionException::class, 'ExtensionInterface');
        $factory($this->container->reveal());
    }
}
