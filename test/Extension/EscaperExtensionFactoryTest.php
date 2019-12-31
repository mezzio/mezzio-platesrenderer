<?php

/**
 * @see       https://github.com/mezzio/mezzio-platesrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-platesrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-platesrenderer/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Plates\Extension;

use Laminas\Escaper\Escaper;
use Laminas\Escaper\Exception\InvalidArgumentException;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Plates\Exception\MissingHelperException;
use Mezzio\Plates\Extension\EscaperExtension;
use Mezzio\Plates\Extension\EscaperExtensionFactory;
use Mezzio\Plates\Extension\UrlExtension;
use Mezzio\Plates\Extension\UrlExtensionFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ProphecyInterface;
use Psr\Container\ContainerInterface;

class EscaperExtensionFactoryTest extends TestCase
{
    /** @var ContainerInterface|ProphecyInterface */
    private $container;

    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function testFactoryWithoutConfig()
    {
        $this->container->has('config')->willReturn(false);

        $factory = new EscaperExtensionFactory();
        $extension = $factory($this->container->reveal());

        $this->assertInstanceOf(EscaperExtension::class, $extension);
    }

    public function testFactoryWithEmptyConfig()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn([]);

        $factory = new EscaperExtensionFactory();
        $extension = $factory($this->container->reveal());

        $this->assertInstanceOf(EscaperExtension::class, $extension);
    }

    public function testFactoryWithInvalidEncodingSetIn()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn([
            'plates' => [
                'encoding' => ''
            ]
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
                'encoding' => 'iso-8859-1'
            ]
        ]);

        $factory = new EscaperExtensionFactory();
        $extension = $factory($this->container->reveal());

        $this->assertInstanceOf(EscaperExtension::class, $extension);
        $this->assertAttributeInstanceOf(Escaper::class, 'escaper', $extension);

        $class = new \ReflectionClass($extension);
        $escaper = $class->getProperty('escaper');
        $escaper->setAccessible(true);
        $escaper = $escaper->getValue($extension);

        $this->assertEquals('iso-8859-1', $escaper->getEncoding());
    }
}
