<?php

namespace Gems\Event\Application;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Loader\JsonFileLoader;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\Loader\MoFileLoader;
use Symfony\Component\Translation\Loader\PoFileLoader;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslatorEvent extends Event
{
    const NAME = 'gems.translations.get';

    /**
     * @var string Current language
     */
    protected string $language;

    /**
     * @var \Zend_Translate
     */
    protected TranslatorInterface $translator;

    /**
     * ZendTranslateEvent constructor.
     *
     * @param \Zend_Translate $translate
     * @param string          $language
     * @param array           $options
     */
    public function __construct(TranslatorInterface $translator, $language)
    {
        $this->translator = $translator;
        $this->language  = $language;
    }

    /**
     * Get the current translate object
     *
     * @return TranslatorInterface
     */
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Add translation options array to the current Translate
     * content: directory of the translation files. Required
     * disableNotices: disables notices. Default true
     *
     * @param array $options
     * @throws \Zend_Translate_Exception
     */
    public function addTranslationByDirectory($directory): void
    {
        $finder = new Finder();
        $files = $finder->files()->in($directory);
        foreach($files as $file) {
            if ($file->getExtension() === 'po' && file_exists($file->getPath() . DIRECTORY_SEPARATOR . $file->getFilenameWithoutExtension()) . 'mo') {
                continue;
            }
            $filenameParts = explode('-', $file->getFilenameWithoutExtension());
            if (count($filenameParts) < 2) {
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
                    $this->translator->addLoader($file->getExtension(), $loaders[$file->getExtension()]);
                }
            }

            $this->translator->addResource($file->getExtension(), $file->getRealPath(), $fileLocale, $domain);
        }
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
}