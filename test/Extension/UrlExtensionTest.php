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
use Mezzio\Plates\Extension\UrlExtension;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ProphecyInterface;

class UrlExtensionTest extends TestCase
{
    /** @var UrlHelper|ProphecyInterface */
    private $urlHelper;

    /** @var ServerUrlHelper|ProphecyInterface */
    private $serverUrlHelper;

    /** @var UrlExtension */
    private $extension;

    public function setUp()
    {
        $this->urlHelper       = $this->prophesize(UrlHelper::class);
        $this->serverUrlHelper = $this->prophesize(ServerUrlHelper::class);

        $this->extension = new UrlExtension(
            $this->urlHelper->reveal(),
            $this->serverUrlHelper->reveal()
        );
    }

    public function testRegistersUrlFunctionWithEngine()
    {
        $engine = $this->prophesize(Engine::class);
        $engine
            ->registerFunction('url', [$this->extension, 'generateUrl'])
            ->shouldBeCalled();
        $engine
            ->registerFunction('serverurl', [$this->extension, 'generateServerUrl'])
            ->shouldBeCalled();

        $this->extension->register($engine->reveal());
    }

    public function urlHelperParams()
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
     *
     * @param null|string $route
     * @param array $params
     */
    public function testGenerateUrlProxiesToUrlHelper($route, array $params)
    {
        $this->urlHelper->generate($route, $params, [], null, [])->willReturn('/success');
        $this->assertEquals('/success', $this->extension->generateUrl($route, $params));
    }

    public function testUrlHelperAcceptsQueryParametersFragmentAndOptions()
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

    public function serverUrlHelperParams()
    {
        return [
            'null'          => [null],
            'absolute-path' => ['/foo/bar'],
            'relative-path' => ['foo/bar'],
        ];
    }

    /**
     * @dataProvider serverUrlHelperParams
     *
     * @param null|string $path
     */
    public function testGenerateServerUrlProxiesToServerUrlHelper($path)
    {
        $this->serverUrlHelper->generate($path)->willReturn('/success');
        $this->assertEquals('/success', $this->extension->generateServerUrl($path));
    }
}
