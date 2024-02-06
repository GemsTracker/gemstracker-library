<?php

namespace Gems\Repository;

use Mezzio\Template\TemplateRendererInterface;
use Mezzio\Twig\TwigRenderer;
use Symfony\Component\Finder\Finder;

class TemplateRepository
{
    public function __construct(
        protected readonly TemplateRendererInterface $templateRenderer
    )
    {}

    public function getAllNamespaces(): array
    {
        $paths = $this->templateRenderer->getPaths();
        $namespaces = [];
        foreach($paths as $path) {
            $namespace = $path->getNamespace();
            if (!in_array($namespace, $namespaces)) {
                $namespaces[] = $namespace;
            }
        }
        sort($namespaces);
        return $namespaces;
    }

    protected function getBaseTemplateName(string $filename): string
    {
        if ($this->templateRenderer instanceof TwigRenderer) {
            return str_replace(['.html.twig', '.html', '.twig'], '', $filename);
        }

        return $filename;
    }

    public function getNamespacePaths(string $namespace): array
    {
        $templatePaths = $this->templateRenderer->getPaths();
        $paths = [];
        foreach($templatePaths as $templatePath) {
            if ($templatePath->getNamespace() === $namespace) {
                $paths[] = realpath($templatePath->getPath());
            }
        }

        return $paths;
    }

    public function getNamespaceTemplates(string $namespace): array
    {
        $paths = $this->getNamespacePaths($namespace);

        $finder = new Finder();
        $finder->depth(0);
        $finder->in($paths);

        $templates = [];
        foreach($finder->files() as $file) {
            $templates[] = $file->getFilename();
        }

        return $templates;
    }

    public function getNamespaceTemplateOptions(string $namespace): array
    {
        $templates = $this->getNamespaceTemplates($namespace);

        $options = [];
        foreach($templates as $templateFilename) {
            $options[$templateFilename] = $this->getBaseTemplateName($templateFilename);
        }

        asort($options);
        return $options;
    }
}