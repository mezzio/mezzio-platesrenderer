<?php

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
                'Zend\Expressive\Template\TemplateRendererInterface' => TemplateRendererInterface::class,
                'Zend\Expressive\Plates\PlatesRenderer'              => PlatesRenderer::class,
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
