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
use Mezzio\Plates\PlatesEngineFactory;
use MezzioTest\Plates\TestAsset\DummyPsrContainer;
use PHPUnit\Framework\TestCase;
use stdClass;

use function restore_error_handler;
use function set_error_handler;

use const E_USER_WARNING;

final class PlatesEngineFactoryTest extends TestCase
{
    private DummyPsrContainer $container;

    public function setUp(): void
    {
        TestAsset\TestExtension::$engine = null;

        $this->container = new DummyPsrContainer();

        $this->container->services[UrlHelper::class]       = $this->createStub(UrlHelper::class);
        $this->container->services[ServerUrlHelper::class] = $this->createStub(ServerUrlHelper::class);
    }

    public function testUrlExtensionIsRegisteredByDefault(): void
    {
        $engine = (new PlatesEngineFactory())($this->container);

        $this->assertTrue($engine->doesFunctionExist('url'));
        $this->assertTrue($engine->doesFunctionExist('serverurl'));
    }

    public function testEscaperExtensionIsRegisteredByDefault(): void
    {
        $engine = (new PlatesEngineFactory())($this->container);

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
        $this->container->services[EscaperExtension::class] = new EscaperExtension();

        $factory = new PlatesEngineFactory();
        $engine  = $factory($this->container);

        $this->assertTrue($engine->doesFunctionExist('escapeHtml'));
        $this->assertTrue($engine->doesFunctionExist('escapeHtmlAttr'));
        $this->assertTrue($engine->doesFunctionExist('escapeJs'));
        $this->assertTrue($engine->doesFunctionExist('escapeCss'));
        $this->assertTrue($engine->doesFunctionExist('escapeUrl'));
    }

    public function testFactoryCanRegisterConfiguredExtensions(): void
    {
        $extensionOne = $this->createMock(ExtensionInterface::class);
        $extensionOne->expects(self::atLeastOnce())
            ->method('register')
            ->with(self::isInstanceOf(PlatesEngine::class));

        $extensionTwo = $this->createMock(ExtensionInterface::class);
        $extensionTwo->expects(self::atLeastOnce())
            ->method('register')
            ->with(self::isInstanceOf(PlatesEngine::class));

        $this->container->services['ExtensionTwo'] = $extensionTwo;
        $this->container->services['config']       = [
            'plates' => [
                'extensions' => [
                    $extensionOne,
                    'ExtensionTwo',
                    TestAsset\TestExtension::class,
                ],
            ],
        ];

        $factory = new PlatesEngineFactory();
        $engine  = $factory($this->container);
        $this->assertInstanceOf(PlatesEngine::class, $engine);

        // Test that the TestExtension was registered. The other two extensions
        // are verified via mocking.
        $this->assertSame($engine, TestAsset\TestExtension::$engine);
    }

    /** @return non-empty-array<non-empty-string, array{non-empty-string}> */
    public static function invalidExtensions(): array
    {
        return [
            'non-class-string' => ['not-a-class'],
        ];
    }

    /** @dataProvider invalidExtensions */
    public function testFactoryRaisesExceptionForInvalidExtensions(string $extension): void
    {
        $this->container->services['config'] = [
            'plates' => [
                'extensions' => [
                    $extension,
                ],
            ],
        ];

        $factory = new PlatesEngineFactory();
        $this->expectException(InvalidExtensionException::class);
        $factory($this->container);
    }

    public function testFactoryRaisesExceptionWhenAttemptingToInjectAnInvalidExtensionService(): void
    {
        $this->container->services['FooExtension'] = new stdClass();
        $this->container->services['config']       = [
            'plates' => [
                'extensions' => [
                    'FooExtension',
                ],
            ],
        ];

        $factory = new PlatesEngineFactory();
        $this->expectException(InvalidExtensionException::class);
        $this->expectExceptionMessage('ExtensionInterface');
        $factory($this->container);
    }

    public function testFactoryRaisesExceptionWhenNonServiceClassIsAnInvalidExtension(): void
    {
        $this->container->services['config'] = [
            'plates' => [
                'extensions' => [
                    stdClass::class,
                ],
            ],
        ];

        $factory = new PlatesEngineFactory();
        $this->expectException(InvalidExtensionException::class);
        $this->expectExceptionMessage('ExtensionInterface');
        $factory($this->container);
    }

    public function testExceptionIsRaisedIfMultiplePathsSpecifyDefaultNamespace(): void
    {
        $this->container->services['config'] = [
            'templates' => [
                'paths' => [
                    0 => __DIR__ . '/TestAsset/bar',
                    1 => __DIR__ . '/TestAsset/baz',
                ],
            ],
        ];

        $factory = new PlatesEngineFactory();

        // phpcs:ignore WebimpressCodingStandard.NamingConventions.ValidVariableName.NotCamelCaps
        set_error_handler(function (int $_errno, string $_errstr): void {
            $this->errorCaught = true;
        }, E_USER_WARNING);
        $factory($this->container);
        restore_error_handler();
        $this->assertTrue($this->errorCaught, 'Did not detect duplicate path for default namespace');
    }

    public function testExceptionIsRaisedIfMultiplePathsInSameNamespace(): void
    {
        $this->container->services['config'] = [
            'templates' => [
                'paths' => [
                    'bar' => [
                        __DIR__ . '/TestAsset/baz',
                        __DIR__ . '/TestAsset/bat',
                    ],
                ],
            ],
        ];

        $factory = new PlatesEngineFactory();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('already being used');
        $factory($this->container);
    }

    public function testSetExtensionByTemplatesConfig(): void
    {
        $this->container->services['config'] = [
            'templates' => [
                'extension' => 'html.twig',
            ],
        ];

        $factory = new PlatesEngineFactory();

        $engine = $factory($this->container);

        $this->assertSame('html.twig', $engine->getFileExtension());
    }

    public function testOverrideExtensionByPlatesConfig(): void
    {
        $this->container->services['config'] = [
            'templates' => [
                'extension' => 'html.twig',
            ],
            'plates'    => [
                'extension' => 'plates.php',
            ],
        ];

        $factory = new PlatesEngineFactory();

        $engine = $factory($this->container);

        $this->assertSame('plates.php', $engine->getFileExtension());
    }

    /** @return non-empty-array<non-empty-string, array{non-empty-list<non-empty-string>}> */
    public static function provideHelpersToUnregister(): array
    {
        return [
            'url-only'        => [[UrlHelper::class]],
            'server-url-only' => [[ServerUrlHelper::class]],
            'both'            => [[ServerUrlHelper::class, UrlHelper::class]],
        ];
    }

    /**
     * @dataProvider provideHelpersToUnregister
     * @param non-empty-list<non-empty-string> $helpers
     */
    public function testUrlExtensionIsNotLoadedIfHelpersAreNotRegistered(array $helpers): void
    {
        foreach ($helpers as $helper) {
            unset($this->container->services[$helper]);
        }

        $factory = new PlatesEngineFactory();
        $engine  = $factory($this->container);

        $this->assertFalse($engine->doesFunctionExist('url'));
        $this->assertFalse($engine->doesFunctionExist('serverurl'));
    }
}
