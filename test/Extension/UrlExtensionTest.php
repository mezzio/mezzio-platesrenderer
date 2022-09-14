<?php

declare(strict_types=1);

namespace MezzioTest\Plates\Extension;

use League\Plates\Engine;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Plates\Extension\UrlExtension;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ProphecyInterface;

class UrlExtensionTest extends TestCase
{
    use ProphecyTrait;

    /** @var UrlHelper|ProphecyInterface */
    private $urlHelper;

    /** @var ServerUrlHelper|ProphecyInterface */
    private $serverUrlHelper;

    private UrlExtension $extension;

    public function setUp(): void
    {
        $this->urlHelper       = $this->prophesize(UrlHelper::class);
        $this->serverUrlHelper = $this->prophesize(ServerUrlHelper::class);

        $this->extension = new UrlExtension(
            $this->urlHelper->reveal(),
            $this->serverUrlHelper->reveal()
        );
    }

    public function testRegistersUrlFunctionWithEngine(): void
    {
        $engine = $this->prophesize(Engine::class);
        $engine
            ->registerFunction('url', $this->urlHelper)
            ->shouldBeCalled();
        $engine
            ->registerFunction('serverurl', $this->serverUrlHelper)
            ->shouldBeCalled();
        $engine
            ->registerFunction('route', [$this->urlHelper, 'getRouteResult'])
            ->shouldBeCalled();

        $this->extension->register($engine->reveal());
    }

    public function urlHelperParams(): array
    {
        return [
            'null'             => [null, []],
            'route-only'       => ['route', []],
            'params-only'      => [null, ['param' => 'value']],
            'route-and-params' => ['route', ['param' => 'value']],
        ];
    }

    /**
     * @dataProvider urlHelperParams
     * @param null|string $route
     * @param array $params
     */
    public function testGenerateUrlProxiesToUrlHelper($route, array $params): void
    {
        $this->urlHelper->generate($route, $params, [], null, [])->willReturn('/success');
        $this->assertEquals('/success', $this->extension->generateUrl($route, $params));
    }

    public function testUrlHelperAcceptsQueryParametersFragmentAndOptions(): void
    {
        $this->urlHelper->generate(
            'resource',
            ['id' => 'sha1'],
            ['foo' => 'bar'],
            'fragment',
            ['reuse_result_params' => true]
        )->willReturn('PATH');

        $this->assertEquals(
            'PATH',
            $this->extension->generateUrl(
                'resource',
                ['id' => 'sha1'],
                ['foo' => 'bar'],
                'fragment',
                ['reuse_result_params' => true]
            )
        );
    }

    public function serverUrlHelperParams(): array
    {
        return [
            'null'          => [null],
            'absolute-path' => ['/foo/bar'],
            'relative-path' => ['foo/bar'],
        ];
    }

    /**
     * @dataProvider serverUrlHelperParams
     * @param null|string $path
     */
    public function testGenerateServerUrlProxiesToServerUrlHelper($path): void
    {
        $this->serverUrlHelper->generate($path)->willReturn('/success');
        $this->assertEquals('/success', $this->extension->generateServerUrl($path));
    }

    public function testGetRouteResultReturnsRouteResultWhenPopulated(): void
    {
        $result = $this->prophesize(RouteResult::class);
        $this->urlHelper->getRouteResult()->willReturn($result->reveal());

        $this->assertInstanceOf(RouteResult::class, $this->extension->getRouteResult());
    }

    public function testGetRouteResultReturnsNullWhenRouteResultNotPopulatedInUrlHelper(): void
    {
        $this->urlHelper->getRouteResult()->willReturn(null);

        $this->assertNull($this->extension->getRouteResult());
    }
}
