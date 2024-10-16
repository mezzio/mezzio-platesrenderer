<?php

declare(strict_types=1);

namespace MezzioTest\Plates\Extension;

use Laminas\Escaper\Escaper;
use Laminas\Escaper\Exception\InvalidArgumentException;
use Mezzio\Plates\Extension\EscaperExtension;
use Mezzio\Plates\Extension\EscaperExtensionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionClass;

final class EscaperExtensionFactoryTest extends TestCase
{
    /** @var ContainerInterface&MockObject */
    private ContainerInterface $container;

    public function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function testFactoryWithoutConfig(): void
    {
        $this->container->method('has')
            ->with('config')
            ->willReturn(false);

        $factory   = new EscaperExtensionFactory();
        $extension = $factory($this->container);

        $this->assertInstanceOf(EscaperExtension::class, $extension);
    }

    public function testFactoryWithEmptyConfig(): void
    {
        $this->container->method('has')
            ->with('config')
            ->willReturn(true);
        $this->container->method('get')
            ->with('config')
            ->willReturn([]);

        $factory   = new EscaperExtensionFactory();
        $extension = $factory($this->container);

        $this->assertInstanceOf(EscaperExtension::class, $extension);
    }

    public function testFactoryWithInvalidEncodingSetIn(): void
    {
        $this->container->method('has')
            ->with('config')
            ->willReturn(true);
        $this->container->method('get')
            ->with('config')
            ->willReturn([
                'plates' => [
                    'encoding' => '',
                ],
            ]);

        $factory = new EscaperExtensionFactory();

        $this->expectException(InvalidArgumentException::class);
        $factory($this->container);
    }

    /**
     * @depends testFactoryWithInvalidEncodingSetIn
     */
    public function testFactoryWithValidEncodingSetIn(): void
    {
        $this->container->method('has')
            ->with('config')
            ->willReturn(true);
        $this->container->method('get')
            ->with('config')
            ->willReturn([
                'plates' => [
                    'encoding' => 'iso-8859-1',
                ],
            ]);

        $factory   = new EscaperExtensionFactory();
        $extension = $factory($this->container);

        $this->assertInstanceOf(EscaperExtension::class, $extension);

        $class   = new ReflectionClass($extension);
        $escaper = $class->getProperty('escaper');
        $escaper = $escaper->getValue($extension);
        $this->assertInstanceOf(Escaper::class, $escaper);

        $this->assertEquals('iso-8859-1', $escaper->getEncoding());
    }
}
