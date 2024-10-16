<?php

declare(strict_types=1);

namespace Mezzio\Plates\Extension;

use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Helper\UrlHelperInterface;
use Mezzio\Router\RouteResult;

/** @psalm-import-type UrlGeneratorOptions from UrlHelperInterface */
class UrlExtension implements ExtensionInterface
{
    public function __construct(private UrlHelper $urlHelper, private ServerUrlHelper $serverUrlHelper)
    {
    }

    /**
     * Register functions with the Plates engine.
     *
     * Registers:
     *
     * - url($route = null, array $params = []) : string
     * - serverurl($path = null) : string
     */
    public function register(Engine $engine): void
    {
        $engine->registerFunction('url', $this->urlHelper);
        $engine->registerFunction('serverurl', $this->serverUrlHelper);
        $engine->registerFunction('route', [$this->urlHelper, 'getRouteResult']);
    }

    /**
     * Get the RouteResult instance of UrlHelper, if any.
     *
     * @deprecated since 2.2.0; to be removed in 3.0.0. This method was originally
     *     used internally to back the route() Plates function; we now register
     *     the UrlHelper::getRouteResult callback directly.
     */
    public function getRouteResult(): ?RouteResult
    {
        return $this->urlHelper->getRouteResult();
    }

    /**
     * Generate a URL from either the currently matched route or the specfied route.
     *
     * @deprecated since 2.2.0; to be removed in 3.0.0. This method was originally
     *     used internally to back the url() Plates function; we now register
     *     UrlHelper instance directly, as it is callable.
     *
     * @param non-empty-string|null $routeName
     * @param array<string, mixed> $routeParams
     * @param array<string, mixed> $queryParams
     * @psalm-param UrlGeneratorOptions $options Can have the following keys:
     *     - router (array): contains options to be passed to the router
     *     - reuse_result_params (bool): indicates if the current RouteResult
     *       parameters will be used, defaults to true
     * @return string
     */
    public function generateUrl(
        ?string $routeName = null,
        array $routeParams = [],
        array $queryParams = [],
        ?string $fragmentIdentifier = null,
        array $options = []
    ) {
        return $this->urlHelper->generate($routeName, $routeParams, $queryParams, $fragmentIdentifier, $options);
    }

    /**
     * Generate a fully qualified URI, relative to $path.
     *
     * @deprecated since 2.2.0; to be removed in 3.0.0. This method was originally
     *     used internally to back the serverurl() Plates function; we now register
     *     the ServerUrl instance directly, as it is callable.
     */
    public function generateServerUrl(?string $path = null): string
    {
        return $this->serverUrlHelper->generate($path);
    }
}
