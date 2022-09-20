<?php

declare(strict_types=1);

namespace MezzioTest\Plates;

use League\Plates\Engine as PlatesEngine;
use League\Plates\Extension\ExtensionInterface;
use LogicException;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Plates\Exception\InvalidExtensionException;
use Mezzio\Plates\Extension\EscaperExtension;
use Mezzio\Plates\Extension\UrlExtension;
use Mezzio\Plates\PlatesEngineFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ProphecyInterface;
use Psr\Container\ContainerInterface;
use stdClass;
use ZendTest\Expressive\Plates\TestAsset\TestExtension;

use function is_string;
use function restore_error_handler;
use function set_error_handler;

use const E_USER_WARNING;

class PlatesEngineFactoryTest extends TestCase
{
    use ProphecyTrait;

    /** @var ContainerInterface|ProphecyInterface */
    private $container;

    public function setUp(): void
    {
        TestAsset\TestExtension::$engine = null;
        $this->container                 = $this->prophesize(ContainerInterface::class);

        $this->container->has(UrlHelper::class)->willReturn(true);
        $this->container->get(UrlHelper::class)->willReturn(
            $this->prophesize(UrlHelper::class)->reveal()
        );

        $this->container->has(ServerUrlHelper::class)->willReturn(true);
        $this->container->get(ServerUrlHelper::class)->willReturn(
            $this->prophesize(ServerUrlHelper::class)->reveal()
        );

        $this->container->has(UrlExtension::class)->willReturn(false);

        $this->container->has('Zend\Expressive\Plates\Extension\UrlExtension')->willReturn(false);
        $this->container->has(EscaperExtension::class)->willReturn(false);
        $this->container->has('Zend\Expressive\Plates\Extension\EscaperExtension')->willReturn(false);
    }

    public function testFactoryReturnsPlatesEngine(): PlatesEngine
    {
        $this->container->has('config')->willReturn(false);
        $factory = new PlatesEngineFactory();
        $engine  = $factory($this->container->reveal());
        $this->assertInstanceOf(PlatesEngine::class, $engine);
        return $engine;
    }

    /**
     * @depends testFactoryReturnsPlatesEngine
     */
    public function testUrlExtensionIsRegisteredByDefault(PlatesEngine $engine): void
    {
        $this->assertTrue($engine->doesFunctionExist('url'));
        $this->assertTrue($engine->doesFunctionExist('serverurl'));
    }

    /**
     * @depends testFactoryReturnsPlatesEngine
     */
    public function testEscaperExtensionIsRegisteredByDefault(PlatesEngine $engine): void
    {
        $this->assertTrue($engine->doesFunctionExist('escapeHtml'));
        $this->assertTrue($engine->doesFunctionExist('escapeHtmlAttr'));
        $this->assertTrue($engine->doesFunctionExist('escapeJs'));
        $this->assertTrue($engine->doesFunctionExist('escapeCss'));
        $this->assertTrue($engine->doesFunctionExist('escapeUrl'));
    }

    /**
     * @depends testEscaperExtensionIsRegisteredByDefault
     */
    public function testEscaperExtensionIsRegisteredFromContainer(): void
    {
        $escaperExtension = new EscaperExtension();

        $this->container->has(EscaperExtension::class)->willReturn(true);
        $this->container->has('config')->willReturn(false);

        $this->container->get(EscaperExtension::class)->willReturn($escaperExtension);

        $factory = new PlatesEngineFactory();
        $engine  = $factory($this->container->reveal());

        $this->assertTrue($engine->doesFunctionExist('escapeHtml'));
        $this->assertTrue($engine->doesFunctionExist('escapeHtmlAttr'));
        $this->assertTrue($engine->doesFunctionExist('escapeJs'));
        $this->assertTrue($engine->doesFunctionExist('escapeCss'));
        $this->assertTrue($engine->doesFunctionExist('escapeUrl'));
    }

    public function testFactoryCanRegisterConfiguredExtensions(): void
    {
        $extensionOne = $this->prophesize(ExtensionInterface::class);
        $extensionOne->register(Argument::type(PlatesEngine::class))->shouldBeCalled();

        $extensionTwo = $this->prophesize(ExtensionInterface::class);
        $extensionTwo->register(Argument::type(PlatesEngine::class))->shouldBeCalled();
        $this->container->has('ExtensionTwo')->willReturn(true);
        $this->container->get('ExtensionTwo')->willReturn($extensionTwo->reveal());

        $this->container->has(TestAsset\TestExtension::class)->willReturn(false);

        $this->container->has(TestExtension::class)->willReturn(false);

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
        $engine  = $factory($this->container->reveal());
        $this->assertInstanceOf(PlatesEngine::class, $engine);

        // Test that the TestExtension was registered. The other two extensions
        // are verified via mocking.
        $this->assertSame($engine, TestAsset\TestExtension::$engine);
    }

    public function invalidExtensions(): array
    {
        return [
            'null'                 => [null],
            'true'                 => [true],
            'false'                => [false],
            'zero'                 => [0],
            'int'                  => [1],
            'zero-float'           => [0.0],
            'float'                => [1.1],
            'non-class-string'     => ['not-a-class'],
            'array'                => [['not-an-extension']],
            'non-extension-object' => [(object) ['extension' => 'not-really']],
        ];
    }

    /**
     * @dataProvider invalidExtensions
     * @param mixed $extension
     */
    public function testFactoryRaisesExceptionForInvalidExtensions($extension): void
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
        $this->expectException(InvalidExtensionException::class);
        $factory($this->container->reveal());
    }

    public function testFactoryRaisesExceptionWhenAttemptingToInjectAnInvalidExtensionService(): void
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
        $this->expectException(InvalidExtensionException::class);
        $this->expectExceptionMessage('ExtensionInterface');
        $factory($this->container->reveal());
    }

    public function testFactoryRaisesExceptionWhenNonServiceClassIsAnInvalidExtension(): void
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
        $this->expectException(InvalidExtensionException::class);
        $this->expectExceptionMessage('ExtensionInterface');
        $factory($this->container->reveal());
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
        $factory = new PlatesEngineFactory();

        // phpcs:ignore WebimpressCodingStandard.NamingConventions.ValidVariableName.NotCamelCaps
        set_error_handler(function (int $_errno, string $_errstr): void {
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
        $factory = new PlatesEngineFactory();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('already being used');
        $factory($this->container->reveal());
    }

    public function provideHelpersToUnregister(): array
    {
        return [
            'url-only'        => [[UrlHelper::class]],
            'server-url-only' => [[ServerUrlHelper::class]],
            'both'            => [[ServerUrlHelper::class, UrlHelper::class]],
        ];
    }

    /**
     * @dataProvider provideHelpersToUnregister
     * @param array $helpers
     */
    public function testUrlExtensionIsNotLoadedIfHelpersAreNotRegistered(array $helpers): void
    {
        $this->container->has('config')->willReturn(false);
        foreach ($helpers as $helper) {
            $this->container->has($helper)->willReturn(false);
        }

        $factory = new PlatesEngineFactory();
        $engine  = $factory($this->container->reveal());

        $this->assertFalse($engine->doesFunctionExist('url'));
        $this->assertFalse($engine->doesFunctionExist('serverurl'));
    }
}
