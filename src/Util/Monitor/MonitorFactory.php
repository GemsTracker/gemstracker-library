<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Monitor
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Util\Monitor;

use Gems\Db\ResultFetcher;
use Gems\Util\Lock\MaintenanceLock;
use Laminas\Permissions\Acl\Acl;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

/**
 * @package    Gems
 * @subpackage Monitor
 * @since      Class available since version 1.0
 */
class MonitorFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): Monitor
    {
        $config = $container->get('config');
        // dump($config);
        $rootDir = $config['rootDir'];

        return new $requestedName(
            $config,
            $container->get(Acl::class),
            $container->get(ResultFetcher::class),
            $container->get(MaintenanceLock::class),
        );
    }
}