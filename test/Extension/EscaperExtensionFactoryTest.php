<?php

/**
 * @see       https://github.com/mezzio/mezzio-platesrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-platesrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-platesrenderer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Plates\Extension;

use Laminas\Escaper\Escaper;
use Laminas\Escaper\Exception\InvalidArgumentException;
use Mezzio\Plates\Extension\EscaperExtension;
use Mezzio\Plates\Extension\EscaperExtensionFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ProphecyInterface;
use Psr\Container\ContainerInterface;
use ReflectionClass;

class EscaperExtensionFactoryTest extends TestCase
{
    use ProphecyTrait;

    /** @var ContainerInterface|ProphecyInterface */
    private $container;

    public function setUp(): void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function testFactoryWithoutConfig()
    {
        $this->container->has('config')->willReturn(false);

        $factory   = new EscaperExtensionFactory();
        $extension = $factory($this->container->reveal());

        $this->assertInstanceOf(EscaperExtension::class, $extension);
    }

    public function testFactoryWithEmptyConfig()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn([]);

        $factory   = new EscaperExtensionFactory();
        $extension = $factory($this->container->reveal());

        $this->assertInstanceOf(EscaperExtension::class, $extension);
    }

    public function testFactoryWithInvalidEncodingSetIn()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn([
            'plates' => [
                'encoding' => '',
            ],
        ]);

        $factory = new EscaperExtensionFactory();

        $this->expectException(InvalidArgumentException::class);
        $factory($this->container->reveal());
    }

    /**
     * @depends testFactoryWithInvalidEncodingSetIn
     */
    public function testFactoryWithValidEncodingSetIn()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn([
            'plates' => [
                'encoding' => 'iso-8859-1',
            ],
        ]);

        $factory   = new EscaperExtensionFactory();
        $extension = $factory($this->container->reveal());

        $this->assertInstanceOf(EscaperExtension::class, $extension);

        $class   = new ReflectionClass($extension);
        $escaper = $class->getProperty('escaper');
        $escaper->setAccessible(true);
        $escaper = $escaper->getValue($extension);
        $this->assertInstanceOf(Escaper::class, $escaper);

        $this->assertEquals('iso-8859-1', $escaper->getEncoding());
    }
}
