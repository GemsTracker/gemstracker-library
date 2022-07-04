<?php

namespace Gems\Translate;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Loader\JsonFileLoader;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\Loader\MoFileLoader;
use Symfony\Component\Translation\Loader\PoFileLoader;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

class TranslationFactory implements FactoryInterface
{

    /**
     * @var array config
     */
    protected $config;

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $this->config = $container->get('config');

        $defaultLocale = $this->getDefaultLocale();

        $translator = $this->getTranslator($defaultLocale);

        return $translator;
    }

    protected function addResourcesToTranslator(Translator $translator): Translator
    {
        $resourcePaths = $this->getResourcePaths();
        $loaders = [];

        foreach($resourcePaths as $path) {
            $finder = new Finder();
            $files = $finder->files()->in($path);
            foreach($files as $file) {
                if ($file->getExtension() === 'po' && file_exists($file->getPath() . DIRECTORY_SEPARATOR . $file->getFilenameWithoutExtension()) . 'mo') {
                    continue;
                }
                $filenameParts = explode('.', $file->getFilenameWithoutExtension());
                if (count($filenameParts) === 0) {
                    continue;
                }
                $domain = $filenameParts[0];
                $fileLocale = $filenameParts[1];

                if ($domain === 'default') {
                    $domain = null;
                }

                if (!isset($loaders[$file->getExtension()])) {
                    $loader = $this->getLoaderFromExtension($file->getExtension());
                    if ($loader instanceof LoaderInterface) {
                        $loaders[$file->getExtension()] = $loader;
                        $translator->addLoader($file->getExtension(), $loaders[$file->getExtension()]);
                    }
                }

                $translator->addResource($file->getExtension(), $file->getRealPath(), $fileLocale, $domain);
            }

        }

        return $translator;
    }

    protected function getDefaultLocale(): string
    {
        if (isset($this->config['locale'], $this->config['locale']['default'])) {
            return $this->config['locale']['default'];
        }

        return 'en';
    }

    protected function getLoaderFromExtension(string $extension): ?LoaderInterface
    {
        switch ($extension) {
            case 'yaml':
            case 'yml':
                return new YamlFileLoader();
            case 'mo':
                return new MoFileLoader();
            case 'po':
                return new PoFileLoader();
            case 'json':
                return new JsonFileLoader();
            case 'xlf':
            case 'xliff':
                return new XliffFileLoader();
            default:
                return null;
        }
    }

    protected function getResourcePaths(): array
    {
        if (isset($this->config['translations'], $this->config['translations']['paths'])) {
            return $this->config['translations']['paths'];
        }
        return [];
    }

    protected function getTranslator(string $locale): Translator
    {
        $translator = new \MUtil\Translate\Translator($locale);
        $translator = $this->addResourcesToTranslator($translator);

        return $translator;
    }



}