<?php

/**
 *
 * @package    Gems
 * @subpackage User\Embed
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\User\Embed;

use Gems\Exception\Coding;
use MUtil\Translate\Translator;
use Zalt\Loader\ProjectOverloader;

/**
 *
 * @package    Gems
 * @subpackage User\Embed
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.8 01-Apr-2020 15:55:10
 */
class EmbedLoader
{
    const AUTHENTICATE          = 'Auth';
    const DEFERRED_USER_LOADER  = 'DeferredUserLoader';
    const REDIRECT              = 'Redirect';

    const SUB_NAMESPACE         = 'User\\Embed';

    /**
     * Each embed helper type must implement an embed helper class or interface derived
     * from EmbedHelperInterface specified in this array.
     *
     * @see HelperInterface
     *
     * @var array containing helperType => helperInterface for all helper classes
     */
    protected array $helperClasses = [
        self::AUTHENTICATE              => EmbeddedAuthInterface::class,
        self::DEFERRED_USER_LOADER      => DeferredUserLoaderInterface::class,
        self::REDIRECT                  => RedirectInterface::class,
    ];

    public function __construct(
        protected readonly ProjectOverloader $overloader,
        protected readonly Translator $translator,
        protected array $config,
    )
    {}

    /**
     * Lookup class for an embedded helper type. This class or interface should at the very least
     * implement the HelperInterface.
     *
     * @see HelperInterface
     *
     * @param string $screenType The type (i.e. lookup directory) to find the associated class for
     * @return string Class/interface name associated with the type
     */
    protected function getInterface(string $helperType): string
    {
        if (isset($this->helperClasses[$helperType])) {
            return $this->helperClasses[$helperType];
        } else {
            throw new Coding("No embedded helper class exists for helper type '$helperType'.");
        }
    }

    /**
     * Loads and initiates an embed class and returns the class
     *
     * @param string $helperName The class name of the individual embed helper to load
     * @param string $helperType The type (i.e. lookup directory with an associated class) of the helper
     * @return object The helper class
     */
    protected function loadClassOfType(string $helperName, string $helperType): object
    {
        $helperClass = $this->getInterface($helperType);

        //$helper = new $helperName();
        $helper = $this->overloader->create($helperName);

        if (! $helper instanceof $helperClass) {
            throw new Coding("The class '$helperName' of type '$helperType' is not an instance of '$helperClass'.");
        }

        /*if ($helper instanceof \MUtil\Registry\TargetInterface) {
            $this->applySource($helper);
        }*/

        return $helper;
    }

    protected function getEmbedClassLabelPairs(array $classNames, string $nameMethod = 'getLabel'): array
    {
        $loader = $this->overloader;
        $labels = array_map(function ($className) use ($loader, $nameMethod) {
            if (class_exists($className)) {
                $object = $loader->create($className);
                return $object->$nameMethod();
            }
            return null;
        }, $classNames);
        return array_combine($classNames, $labels);
    }

    /**
     *
     * @return array helpername => string
     */
    public function listAuthenticators(): array
    {
        if (isset($this->config['embed']['auth'])) {
            $authenticators = $this->config['embed']['auth'];

            return $this->getEmbedClassLabelPairs($authenticators);
        }
        return [];
    }

    /**
     *
     * @return array
     */
    public function listCrumbOptions(): array
    {
        return [
            '' => $this->translator->_('(use project settings)'),
            'no_display' => $this->translator->_('Hide crumbs'),
            'no_top' => $this->translator->_('Hide topmost crumb'),
        ];
    }

    /**
     *
     * @return array helpername => string
     */
    public function listDeferredUserLoaders(): array
    {
        if (isset($this->config['embed']['deferredUserLoader'])) {
            $deferredUserLoaders =  $this->config['embed']['deferredUserLoader'];
            return $this->getEmbedClassLabelPairs($deferredUserLoaders);
        }
        return [];
    }

    /**
     * Get an array of layouts in Gemstracker and Project
     *
     * @return array
     */
    public function listLayouts(): array
    {
        $layouts = [
            '' => $this->translator->_('Do not change layout'),
        ];

        return $layouts;
    }

    /**
     *
     * @return array helpername => string
     */
    public function listRedirects(): array
    {
        if (isset($this->config['embed']['redirect'])) {
            $redirects = $this->config['embed']['redirect'];
            return $this->getEmbedClassLabelPairs($redirects);
        }
        return [];
    }

    /**
     * Get an array of Escort styles
     *
     * @return array
     */
    public function listStyles()
    {
        // TODO: reimplement multi styles
        $styles = [];

        return ['' => $this->translator->_('Use organization style')] + $styles;
    }

    /**
     *
     * @param string $helperName Name of the helper class
     * @return EmbeddedAuthInterface
     */
    public function loadAuthenticator(string $helperName): EmbeddedAuthInterface
    {
        return $this->loadClassOfType($helperName, self::AUTHENTICATE);
    }

    /**
     *
     * @param string $helperName Name of the helper class
     * @return DeferredUserLoaderInterface
     */
    public function loadDeferredUserLoader(string $helperName): DeferredUserLoaderInterface
    {
        return $this->loadClassOfType($helperName, self::DEFERRED_USER_LOADER);
    }

    /**
     *
     * @param string $helperName Name of the helper class
     * @return RedirectInterface
     */
    public function loadRedirect(string $helperName): RedirectInterface
    {
        return $this->loadClassOfType($helperName, self::REDIRECT);
    }
}
