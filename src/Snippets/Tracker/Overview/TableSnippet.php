<?php
/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker\Overview;

use Zalt\Model\MetaModelInterface;
use Zalt\Snippets\ModelBridge\TableBridge;

/**
 * Display a tabel that links to the track maintenance
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.4
 */
class TableSnippet extends \Gems\Snippets\ModelTableSnippet
{
    protected $class = 'browser table visualtrack';

    /**
     * Menu actions to show in Show box.
     *
     * If controller is numeric $menuActionController is used, otherwise
     * the key specifies the controller.
     *
     * @var array (int/controller => action)
     */
    public array $menuShowRoutes = ['track-builder.track-maintenance.show'];

    public function getRouteMaps(MetaModelInterface $metaModel): array
    {
        return ['trackId' => 'gtr_id_track'];
    }

    protected function getShowUrls(TableBridge $bridge, array $keys): array
    {
        return $this->menuHelper->getLateRouteUrls($this->menuShowRoutes, $keys, $bridge);
    }
}