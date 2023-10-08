<?php

declare(strict_types=1);


/**
 * @package    GemsTest
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace GemsTest\Model;

use Gems\Locale\Locale;
use Gems\Repository\TokenRepository;
use Gems\Translate\TranslationFactory;
use GemsTest\Repository\MockTokenRepository;
use Zalt\Base\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;
use Zalt\Loader\ProjectOverloaderFactory;
use Zalt\Mock\MockTranslator;
use Zalt\Mock\SimpleServiceManager;
use Zalt\Model\MetaModelLoader;
use Zalt\Model\MetaModelLoaderFactory;

/**
 * @package    GemsTest
 * @subpackage Model
 * @since      Class available since version 1.0
 */
trait GemsModelTestTrait
{
    public array $serverManagerConfig = [];

    public function getModelLoader(): MetaModelLoader
    {
        static $loader;

        if ($loader instanceof MetaModelLoader) {
            return $loader;
        }

        $sm = $this->getServiceManager();
        $overFc = new ProjectOverloaderFactory();
        $sm->set(ProjectOverloader::class, $overFc($sm));

        $mmlf   = new MetaModelLoaderFactory();
        $loader = $mmlf($sm);

        return $loader;
    }

    public function getServiceManager(): SimpleServiceManager
    {
        static $sm;

        if (! $sm instanceof SimpleServiceManager) {
            $sm = new SimpleServiceManager(['config' => $this->serverManagerConfig]);

            // Add required classes to the service manager
            $sm->set(TranslatorInterface::class, new MockTranslator());
            $sm->set(Locale::class, new Locale([]));
            $tf = new TranslationFactory();
            $sm->set(TranslatorInterface::class, $tf($sm, ''));
            $sm->set(TokenRepository::class, new MockTokenRepository($sm->get(TranslatorInterface::class)));
        }

        return $sm;
    }
}