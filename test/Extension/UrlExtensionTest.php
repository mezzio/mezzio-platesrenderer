<?php

declare(strict_types=1);

namespace MezzioTest\Plates\Extension;

use League\Plates\Engine;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Plates\Extension\UrlExtension;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UrlExtensionTest extends TestCase
{
    /** @var UrlHelper&MockObject */
    private UrlHelper $urlHelper;

    /** @var ServerUrlHelper&MockObject */
    private ServerUrlHelper $serverUrlHelper;

    private UrlExtension $extension;

    public function setUp(): void
    {
        $this->urlHelper       = $this->createMock(UrlHelper::class);
        $this->serverUrlHelper = $this->createMock(ServerUrlHelper::class);

        $this->extension = new UrlExtension(
            $this->urlHelper,
            $this->serverUrlHelper
        );
    }

    public function testRegistersUrlFunctionWithEngine(): void
    {
        $engine = $this->createMock(Engine::class);
        $engine
            ->expects(self::exactly(3))
            ->method('registerFunction')
            ->with(
                self::logicalOr('url', 'serverurl', 'route'),
                self::logicalOr($this->urlHelper, $this->serverUrlHelper, [$this->urlHelper, 'getRouteResult']),
            );

        $this->extension->register($engine);
    }

    /** @return array<string, array{0: null|non-empty-string, 1: array<string, mixed>}> */
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
     * @param null|non-empty-string $route
     * @param array<string, mixed> $params
     */
    public function testGenerateUrlProxiesToUrlHelper($route, array $params): void
    {
        $this->urlHelper->method('generate')
            ->with($route, $params, [], null, [])
            ->willReturn('/success');

        $this->assertEquals('/success', $this->extension->generateUrl($route, $params));
    }

    public function testUrlHelperAcceptsQueryParametersFragmentAndOptions(): void
    {
        $this->urlHelper->method('generate')
            ->with(
                'resource',
                ['id' => 'sha1'],
                ['foo' => 'bar'],
                'fragment',
                ['reuse_result_params' => true]
            )
            ->willReturn('PATH');

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
        $this->serverUrlHelper->method('generate')
            ->with($path)
            ->willReturn('/success');
        $this->assertEquals('/success', $this->extension->generateServerUrl($path));
    }

    public function testGetRouteResultReturnsRouteResultWhenPopulated(): void
    {
        $result = $this->createMock(RouteResult::class);
        $this->urlHelper->method('getRouteResult')
            ->willReturn($result);

        $this->assertInstanceOf(RouteResult::class, $this->extension->getRouteResult());
    }

    public function testGetRouteResultReturnsNullWhenRouteResultNotPopulatedInUrlHelper(): void
    {
        $this->urlHelper->method('getRouteResult')
            ->willReturn(null);

        $this->assertNull($this->extension->getRouteResult());
    }
}
