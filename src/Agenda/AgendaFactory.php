<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Agenda;

use Gems\Repository\OrganizationRepository;
use Psr\Container\ContainerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @since      Class available since version 1.9.2
 */
class AgendaFactory
{
    public function __invoke(ContainerInterface $container): Agenda
    {
        $overloader = $container->get(ProjectOverloader::class);
        $subLoader = $overloader->createSubFolderOverloader('Agenda');

        return new Agenda(
            $subLoader, 
            $container->get(TranslatorInterface::class),
            $container->get(AdapterInterface::class),
            $container->get(\Zend_Db_Adapter_Abstract::class),
            $container->get(OrganizationRepository::class),
        );
    }
}