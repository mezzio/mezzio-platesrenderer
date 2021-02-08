<?php

/**
 * @see       https://github.com/mezzio/mezzio-platesrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-platesrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-platesrenderer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Plates\Extension;

use League\Plates\Engine;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Plates\Exception\MissingHelperException;
use Mezzio\Plates\Extension\UrlExtension;
use Mezzio\Plates\Extension\UrlExtensionFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ProphecyInterface;
use Psr\Container\ContainerInterface;

class UrlExtensionFactoryTest extends TestCase
{
    use ProphecyTrait;

    /** @var ContainerInterface|ProphecyInterface */
    private $container;

    /** @var UrlHelper|ProphecyInterface */
    private $urlHelper;

    /** @var ServerUrlHelper|ProphecyInterface */
    private $serverUrlHelper;

    public function setUp(): void
    {
        $this->container       = $this->prophesize(ContainerInterface::class);
        $this->urlHelper       = $this->prophesize(UrlHelper::class);
        $this->serverUrlHelper = $this->prophesize(ServerUrlHelper::class);
    }

    public function testFactoryReturnsUrlExtensionInstanceWhenHelpersArePresent()
    {
        $urlHelper       = $this->urlHelper->reveal();
        $serverUrlHelper = $this->serverUrlHelper->reveal();

        $this->container->has(UrlHelper::class)->willReturn(true);
        $this->container->get(UrlHelper::class)->willReturn($urlHelper);
        $this->container->has(ServerUrlHelper::class)->willReturn(true);
        $this->container->get(ServerUrlHelper::class)->willReturn($serverUrlHelper);

        $factory   = new UrlExtensionFactory();
        $extension = $factory($this->container->reveal());
        $this->assertInstanceOf(UrlExtension::class, $extension);

        $engine = $this->createMock(Engine::class);
        $engine->method('registerFunction')
            ->withConsecutive(
                ['url', $this->equalTo($urlHelper)],
                ['serverurl', $this->equalTo($serverUrlHelper)]
            );

        $extension->register($engine);
    }

    public function testFactoryRaisesExceptionIfUrlHelperIsMissing()
    {
        $this->container->has(UrlHelper::class)->willReturn(false);
        $this->container->has(UrlHelper::class)->willReturn(false);
        $this->container->get(UrlHelper::class)->shouldNotBeCalled();
        $this->container->get(UrlHelper::class)->shouldNotBeCalled();
        $this->container->has(ServerUrlHelper::class)->shouldNotBeCalled();
        $this->container->has(ServerUrlHelper::class)->shouldNotBeCalled();
        $this->container->get(ServerUrlHelper::class)->shouldNotBeCalled();
        $this->container->get(ServerUrlHelper::class)->shouldNotBeCalled();

        $factory = new UrlExtensionFactory();

        $this->expectException(MissingHelperException::class);
        $this->expectExceptionMessage(UrlHelper::class);
        $factory($this->container->reveal());
    }

    public function testFactoryRaisesExceptionIfServerUrlHelperIsMissing()
    {
        $this->container->has(UrlHelper::class)->willReturn(true);
        $this->container->get(UrlHelper::class)->shouldNotBeCalled();
        $this->container->get(UrlHelper::class)->shouldNotBeCalled();
        $this->container->has(ServerUrlHelper::class)->willReturn(false);
        $this->container->has(ServerUrlHelper::class)->willReturn(false);
        $this->container->get(ServerUrlHelper::class)->shouldNotBeCalled();
        $this->container->get(ServerUrlHelper::class)->shouldNotBeCalled();

        $factory = new UrlExtensionFactory();

        $this->expectException(MissingHelperException::class);
        $this->expectExceptionMessage(ServerUrlHelper::class);
        $factory($this->container->reveal());
    }
}
