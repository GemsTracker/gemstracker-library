<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model;

use Gems\Legacy\CurrentUserRepository;
use Gems\Model\Bridge\GemsFormBridge;
use Psr\Container\ContainerInterface;
use Zalt\Loader\ProjectOverloader;

/**
 *
 * @package    Gems
 * @subpackage Model
 * @since      Class available since version 1.9.2
 */
class MetaModelLoaderFactory extends \Zalt\Model\MetaModelLoaderFactory
{
    public function __invoke(ContainerInterface $container): MetaModelLoader
    {
        $config     = $this->checkConfig($container->get('config'));
        $overloader = $container->get(ProjectOverloader::class);
        $userRepos  = $container->get(CurrentUserRepository::class); 

        if (! isset($config['model']['translateDatabaseFields'])) {
            $config['model']['translateDatabaseFields'] = false;
        }
        return new MetaModelLoader($overloader->createSubFolderOverloader('Model'), $config['model'], $userRepos);
    }
}