<?php

/**
 * @see       https://github.com/mezzio/mezzio-platesrenderer for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-platesrenderer/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-platesrenderer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Plates;

use Mezzio\Template\TemplateRendererInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'templates'    => $this->getTemplates(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'aliases'   => [
                TemplateRendererInterface::class => PlatesRenderer::class,

                // Legacy Zend Framework aliases
                \Zend\Expressive\Template\TemplateRendererInterface::class => TemplateRendererInterface::class,
                \Zend\Expressive\Plates\PlatesRenderer::class              => PlatesRenderer::class,
            ],
            'factories' => [
                PlatesRenderer::class => PlatesRendererFactory::class,
            ],
        ];
    }

    public function getTemplates(): array
    {
        return [
            'extension' => 'phtml',
            'paths'     => [],
        ];
    }
}
