<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage SnippetsLoader
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsLoader;

use Gems\Layout\LayoutRenderer;
use Psr\Container\ContainerInterface;
use Zalt\SnippetsLoader\SnippetLoader;

/**
 *
 * @package    Gems
 * @subpackage SnippetsLoader
 * @since      Class available since version 1.9.2
 */
class GemsSnippetResponderFactory
{
    public function __invoke(ContainerInterface $container): GemsSnippetResponder
    {
        return new GemsSnippetResponder($container->get(SnippetLoader::class), $container->get(LayoutRenderer::class));
    }
}