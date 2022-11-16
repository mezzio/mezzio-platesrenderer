<?php

declare(strict_types=1);

namespace Mezzio\Plates;

use League\Plates\Engine;
use League\Plates\Template\Folder;
use Mezzio\Template\ArrayParametersTrait;
use Mezzio\Template\Exception;
use Mezzio\Template\TemplatePath;
use Mezzio\Template\TemplateRendererInterface;
use ReflectionProperty;

use function get_debug_type;
use function sprintf;
use function trigger_error;
use function trim;

use const E_USER_WARNING;

/**
 * Template implementation bridging league/plates
 */
class PlatesRenderer implements TemplateRendererInterface
{
    use ArrayParametersTrait;

    private Engine $template;

    public function __construct(?Engine $template = null)
    {
        if (null === $template) {
            $template = $this->createTemplate();
        }
        $this->template = $template;
    }

    /**
     * {@inheritDoc}
     */
    public function render(string $name, $params = []): string
    {
        $params = $this->normalizeParams($params);
        return $this->template->render($name, $params);
    }

    /**
     * Add a path for template
     *
     * Multiple calls to this method without a namespace will trigger an
     * E_USER_WARNING and act as a no-op. Plates does not handle non-namespaced
     * folders, only the default directory; overwriting the default directory
     * is likely unintended.
     */
    public function addPath(string $path, ?string $namespace = null): void
    {
        if (! $namespace && ! $this->template->getDirectory()) {
            $this->template->setDirectory($path);
            return;
        }

        if (! $namespace) {
            trigger_error('Cannot add duplicate un-namespaced path in Plates template adapter', E_USER_WARNING);
            return;
        }

        $this->template->addFolder($namespace, $path, true);
    }

    /**
     * {@inheritDoc}
     */
    public function getPaths(): array
    {
        $paths = $this->template->getDirectory()
            ? [$this->getDefaultPath()]
            : [];

        foreach ($this->getPlatesFolders() as $folder) {
            $paths[] = new TemplatePath($folder->getPath(), $folder->getName());
        }
        return $paths;
    }

    /**
     * Proxies to the Plate Engine's `addData()` method.
     *
     * {@inheritDoc}
     */
    public function addDefaultParam(string $templateName, string $param, mixed $value): void
    {
        if ('' === trim($templateName)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '$templateName must be a non-empty string; received %s',
                get_debug_type($templateName)
            ));
        }

        if ('' === trim($param)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '$param must be a non-empty string; received %s',
                get_debug_type($param)
            ));
        }

        $params = [$param => $value];

        if ($templateName === self::TEMPLATE_ALL) {
            $templateName = null;
        }

        $this->template->addData($params, $templateName);
    }

    /**
     * Create a default Plates engine
     */
    private function createTemplate(): Engine
    {
        return new Engine();
    }

    /**
     * Create and return a TemplatePath representing the default Plates directory.
     */
    private function getDefaultPath(): TemplatePath
    {
        return new TemplatePath($this->template->getDirectory());
    }

    /**
     * Return the internal array of plates folders.
     *
     * @return Folder[]
     */
    private function getPlatesFolders(): array
    {
        $folders = $this->template->getFolders();
        $r       = new ReflectionProperty($folders, 'folders');
        $r->setAccessible(true);
        return $r->getValue($folders);
    }
}
