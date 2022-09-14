<?php

declare(strict_types=1);

namespace MezzioTest\Plates;

use ArrayObject;
use League\Plates\Engine;
use Mezzio\Plates\PlatesRenderer;
use Mezzio\Template\Exception;
use Mezzio\Template\TemplatePath;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

use function array_shift;
use function file_get_contents;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;
use function str_replace;
use function uniqid;
use function var_export;

use const E_NOTICE;
use const E_USER_WARNING;

class PlatesRendererTest extends TestCase
{
    use ProphecyTrait;

    private Engine $platesEngine;

    private bool $error;

    public function setUp(): void
    {
        $this->error        = false;
        $this->platesEngine = new Engine();
    }

    public function assertTemplatePath(string $path, TemplatePath $templatePath, ?string $message = null): void
    {
        $message = $message ?: sprintf('Failed to assert TemplatePath contained path %s', $path);
        $this->assertEquals($path, $templatePath->getPath(), $message);
    }

    public function assertTemplatePathString(string $path, TemplatePath $templatePath, ?string $message = null): void
    {
        $message = $message ?: sprintf('Failed to assert TemplatePath casts to string path %s', $path);
        $this->assertEquals($path, (string) $templatePath, $message);
    }

    public function assertTemplatePathNamespace(
        string $namespace,
        TemplatePath $templatePath,
        ?string $message = null
    ): void {
        $message = $message ?: sprintf(
            'Failed to assert TemplatePath namespace matched %s',
            var_export($namespace, true)
        );
        $this->assertEquals($namespace, $templatePath->getNamespace(), $message);
    }

    public function assertEmptyTemplatePathNamespace(TemplatePath $templatePath, ?string $message = null): void
    {
        $message = $message ?: 'Failed to assert TemplatePath namespace was empty';
        $this->assertEmpty($templatePath->getNamespace(), $message);
    }

    public function assertEqualTemplatePath(
        TemplatePath $expected,
        TemplatePath $received,
        ?string $message = null
    ): void {
        $message = $message ?: 'Failed to assert TemplatePaths are equal';
        if (
            $expected->getPath() !== $received->getPath()
            || $expected->getNamespace() !== $received->getNamespace()
        ) {
            $this->fail($message);
        }
    }

    public function testCanProvideEngineAtInstantiation(): void
    {
        $renderer = new PlatesRenderer($this->platesEngine);
        $this->assertInstanceOf(PlatesRenderer::class, $renderer);
        $this->assertEmpty($renderer->getPaths());
    }

    public function testLazyLoadsEngineAtInstantiationIfNoneProvided(): void
    {
        $renderer = new PlatesRenderer();
        $this->assertInstanceOf(PlatesRenderer::class, $renderer);
        $this->assertEmpty($renderer->getPaths());
    }

    public function testCanAddPath(): PlatesRenderer
    {
        $renderer = new PlatesRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $paths = $renderer->getPaths();
        $this->assertIsArray($paths);
        $this->assertCount(1, $paths);
        $this->assertTemplatePath(__DIR__ . '/TestAsset', $paths[0]);
        $this->assertTemplatePathString(__DIR__ . '/TestAsset', $paths[0]);
        $this->assertEmptyTemplatePathNamespace($paths[0]);
        return $renderer;
    }

    /**
     * @param PlatesRenderer $renderer
     * @depends testCanAddPath
     */
    public function testAddingSecondPathWithoutNamespaceIsANoopAndRaisesWarning($renderer): void
    {
        $paths = $renderer->getPaths();
        $path  = array_shift($paths);

        // phpcs:ignore WebimpressCodingStandard.NamingConventions.ValidVariableName.NotCamelCaps
        set_error_handler(function (int $_errno, string $message): bool {
            $this->error = true;
            $this->assertStringContainsString('duplicate', $message);
            return true;
        }, E_USER_WARNING);
        $renderer->addPath(__DIR__);
        restore_error_handler();

        $this->assertTrue($this->error, 'Error handler was not triggered when calling addPath() multiple times');

        $paths = $renderer->getPaths();
        $this->assertIsArray($paths);
        $this->assertCount(1, $paths);
        $test = array_shift($paths);
        $this->assertEqualTemplatePath($path, $test);
    }

    public function testCanAddPathWithNamespace(): void
    {
        $renderer = new PlatesRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset', 'test');
        $paths = $renderer->getPaths();
        $this->assertIsArray($paths);
        $this->assertCount(1, $paths);
        $this->assertTemplatePath(__DIR__ . '/TestAsset', $paths[0]);
        $this->assertTemplatePathString(__DIR__ . '/TestAsset', $paths[0]);
        $this->assertTemplatePathNamespace('test', $paths[0]);
    }

    public function testDelegatesRenderingToUnderlyingImplementation(): void
    {
        $renderer = new PlatesRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name   = 'Plates';
        $result = $renderer->render('plates', ['name' => $name]);
        $this->assertStringContainsString($name, $result);
        $content = file_get_contents(__DIR__ . '/TestAsset/plates.php');
        $content = str_replace('<?=$this->e($name)?>', $name, $content);
        $this->assertEquals($content, $result);
    }

    public function invalidParameterValues(): array
    {
        return [
            'true'       => [true],
            'false'      => [false],
            'zero'       => [0],
            'int'        => [1],
            'zero-float' => [0.0],
            'float'      => [1.1],
            'string'     => ['value'],
        ];
    }

    /**
     * @dataProvider invalidParameterValues
     * @param mixed $params
     */
    public function testRenderRaisesExceptionForInvalidParameterTypes($params): void
    {
        $renderer = new PlatesRenderer();
        $this->expectException(Exception\InvalidArgumentException::class);
        $renderer->render('foo', $params);
    }

    public function testCanRenderWithNullParams(): void
    {
        $renderer = new PlatesRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $result  = $renderer->render('plates-null', null);
        $content = file_get_contents(__DIR__ . '/TestAsset/plates-null.php');
        $this->assertEquals($content, $result);
    }

    public function objectParameterValues(): array
    {
        $names = [
            'stdClass'    => uniqid(),
            'ArrayObject' => uniqid(),
        ];

        return [
            'stdClass'    => [(object) ['name' => $names['stdClass']], $names['stdClass']],
            'ArrayObject' => [new ArrayObject(['name' => $names['ArrayObject']]), $names['ArrayObject']],
        ];
    }

    /**
     * @dataProvider objectParameterValues
     * @param object $params
     * @param string $search
     */
    public function testCanRenderWithParameterObjects($params, $search): void
    {
        $renderer = new PlatesRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $result = $renderer->render('plates', $params);
        $this->assertStringContainsString($search, $result);
        $content = file_get_contents(__DIR__ . '/TestAsset/plates.php');
        $content = str_replace('<?=$this->e($name)?>', $search, $content);
        $this->assertEquals($content, $result);
    }

    /**
     * @group namespacing
     */
    public function testProperlyResolvesNamespacedTemplate(): void
    {
        $renderer = new PlatesRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset/test', 'test');

        $expected = file_get_contents(__DIR__ . '/TestAsset/test/test.php');
        $test     = $renderer->render('test::test');

        $this->assertSame($expected, $test);
    }

    public function testAddParameterToOneTemplate(): void
    {
        $renderer = new PlatesRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name = 'Plates';
        $renderer->addDefaultParam('plates', 'name', $name);
        $result  = $renderer->render('plates');
        $content = file_get_contents(__DIR__ . '/TestAsset/plates.php');
        $content = str_replace('<?=$this->e($name)?>', $name, $content);
        $this->assertEquals($content, $result);

        // phpcs:ignore WebimpressCodingStandard.NamingConventions.ValidVariableName.NotCamelCaps
        set_error_handler(function (int $_errno, string $message): bool {
            $this->assertStringContainsString('Undefined variable: name', $message);
            return true;
        }, E_NOTICE);
        $renderer->render('plates-2');
        restore_error_handler();

        $content = str_replace('<?=$this->e($name)?>', '', $content);
        $this->assertEquals($content, $result);
    }

    public function testAddSharedParameters(): void
    {
        $renderer = new PlatesRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name = 'Plates';
        $renderer->addDefaultParam($renderer::TEMPLATE_ALL, 'name', $name);
        $result  = $renderer->render('plates');
        $content = file_get_contents(__DIR__ . '/TestAsset/plates.php');
        $content = str_replace('<?=$this->e($name)?>', $name, $content);
        $this->assertEquals($content, $result);
        $result  = $renderer->render('plates-2');
        $content = file_get_contents(__DIR__ . '/TestAsset/plates-2.php');
        $content = str_replace('<?=$this->e($name)?>', $name, $content);
        $this->assertEquals($content, $result);
    }

    public function testOverrideSharedParametersPerTemplate(): void
    {
        $renderer = new PlatesRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name  = 'Plates';
        $name2 = 'Saucers';
        $renderer->addDefaultParam($renderer::TEMPLATE_ALL, 'name', $name);
        $renderer->addDefaultParam('plates-2', 'name', $name2);
        $result  = $renderer->render('plates');
        $content = file_get_contents(__DIR__ . '/TestAsset/plates.php');
        $content = str_replace('<?=$this->e($name)?>', $name, $content);
        $this->assertEquals($content, $result);
        $result  = $renderer->render('plates-2');
        $content = file_get_contents(__DIR__ . '/TestAsset/plates-2.php');
        $content = str_replace('<?=$this->e($name)?>', $name2, $content);
        $this->assertEquals($content, $result);
    }

    public function testOverrideSharedParametersAtRender(): void
    {
        $renderer = new PlatesRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name  = 'Plates';
        $name2 = 'Saucers';
        $renderer->addDefaultParam($renderer::TEMPLATE_ALL, 'name', $name);
        $result  = $renderer->render('plates', ['name' => $name2]);
        $content = file_get_contents(__DIR__ . '/TestAsset/plates.php');
        $content = str_replace('<?=$this->e($name)?>', $name2, $content);
        $this->assertEquals($content, $result);
    }
}
