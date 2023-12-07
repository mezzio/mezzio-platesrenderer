<?php

declare(strict_types=1);

namespace MezzioTest\Plates\Extension;

use League\Plates\Engine;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Plates\Exception\MissingHelperException;
use Mezzio\Plates\Extension\UrlExtension;
use Mezzio\Plates\Extension\UrlExtensionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class UrlExtensionFactoryTest extends TestCase
{
    /** @var ContainerInterface&MockObject */
    private ContainerInterface $container;

    /** @var UrlHelper&MockObject */
    private UrlHelper $urlHelper;

    /** @var ServerUrlHelper&MockObject */
    private ServerUrlHelper $serverUrlHelper;

    public function setUp(): void
    {
        $this->container       = $this->createMock(ContainerInterface::class);
        $this->urlHelper       = $this->createMock(UrlHelper::class);
        $this->serverUrlHelper = $this->createMock(ServerUrlHelper::class);
    }

    public function testFactoryReturnsUrlExtensionInstanceWhenHelpersArePresent(): void
    {
        $urlHelper       = $this->urlHelper;
        $serverUrlHelper = $this->serverUrlHelper;

        $this->container->method('has')
            ->willReturnMap([
                [UrlHelper::class, true],
                [ServerUrlHelper::class, true],
            ]);
        $this->container->method('get')
            ->willReturnMap([
                [UrlHelper::class, $urlHelper],
                [ServerUrlHelper::class, $serverUrlHelper],
            ]);

        $factory   = new UrlExtensionFactory();
        $extension = $factory($this->container);
        $this->assertInstanceOf(UrlExtension::class, $extension);

        $engine = $this->createMock(Engine::class);
        $engine->method('registerFunction')
            ->withConsecutive(
                ['url', $this->equalTo($urlHelper)],
                ['serverurl', $this->equalTo($serverUrlHelper)]
            );

        $extension->register($engine);
    }

    public function testFactoryRaisesExceptionIfUrlHelperIsMissing(): void
    {
        $this->container->method('has')
            ->willReturnMap([
                [UrlHelper::class, false],
                [ServerUrlHelper::class, false],
            ]);
        $this->container->expects(self::never())
            ->method('get');

        $factory = new UrlExtensionFactory();

        $this->expectException(MissingHelperException::class);
        $this->expectExceptionMessage(UrlHelper::class);
        $factory($this->container);
    }

    public function testFactoryRaisesExceptionIfServerUrlHelperIsMissing(): void
    {
        $this->container->method('has')
            ->willReturnMap([
                [UrlHelper::class, true],
                [ServerUrlHelper::class, false],
            ]);
        $this->container->expects(self::never())
            ->method('get');

        $factory = new UrlExtensionFactory();

        $this->expectException(MissingHelperException::class);
        $this->expectExceptionMessage(ServerUrlHelper::class);
        $factory($this->container);
    }
}
