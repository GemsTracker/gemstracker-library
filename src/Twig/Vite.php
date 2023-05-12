<?php

namespace Gems\Twig;

use Exception;
use Mezzio\Helper\UrlHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Vite extends AbstractExtension
{
    protected string $buildDir = 'build';

    protected string $publicDir;

    protected string $hotFile = 'vite-dev-server';

    public function __construct(protected UrlHelper $urlHelper, array $config)
    {
        if (isset($config['publicDir'])) {
            $this->publicDir = $config['publicDir'];
        }
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('vite', [$this, 'renderViteTag'], ['is_safe' => ['html']]),
        ];
    }

    public function renderViteTag(array|string $resources): string
    {
        $resources = (array)$resources;

        if ($this->isRunningHot()) {
            $assetDir = file_get_contents($this->hotFile());
            $tags = [];
            foreach($resources as $resourceName) {
                $tags[] = $this->makeTag($assetDir . '/' . $resourceName);
            }
            return join("\n", $tags);
        }

        $assetDir = $this->publicDir . '/' . $this->buildDir;
        $manifest = $this->getManifest($assetDir);

        $tags = [];
        foreach($resources as $resourceName) {
            if (!isset($manifest[$resourceName], $manifest[$resourceName]['file'])) {
                throw new Exception("Resource {$resourceName} not found.");
            }
            $tags[] = $this->makeTag($this->urlHelper->getBasePath() . $this->buildDir . '/' . $manifest[$resourceName]['file']);
        }
        return join("\n", $tags);
    }

    protected function getManifest(string $assetDir): array
    {
        $manifestLocation = $assetDir . '/manifest.json';

        if (is_file($manifestLocation)) {
            $rawManifest = file_get_contents($manifestLocation);
            $manifest = json_decode($rawManifest, true);
            if ($manifest) {
                return $manifest;
            }
        }

        throw new Exception("Vite manifest not found at: {$manifestLocation}");
    }

    /**
     * Get the Vite "hot" file path.
     *
     * @return string
     */
    public function hotFile(): string
    {
        return $this->publicDir . ('/vite-dev-server');
    }

    /**
     * Determine whether the given path is a CSS file.
     *
     * @param  string  $path
     * @return bool
     */
    protected function isCssPath(string $path): bool
    {
        return preg_match('/\.(css|less|sass|scss|styl|stylus|pcss|postcss)$/', $path) === 1;
    }

    /**
     * Determine if the HMR server is running.
     *
     * @return bool
     */
    protected function isRunningHot(): bool
    {
        return is_file($this->hotFile());
    }



    protected function makeTag(string $src): string
    {
        if ($this->isCssPath($src)) {
            return $this->makeStyleSheetTag($src);
        }
        return $this->makeScriptTag($src);
    }

    protected function makeScriptTag(string $src): string
    {
        return "<script src=\"$src\" type=\"module\"></script>";
    }

    protected function makeStyleSheetTag(string $src): string
    {
        return "<link href=\"$src\" rel=\"stylesheet\" />";
    }

}