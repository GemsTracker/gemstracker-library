<?php

namespace Gems\Snippets\Tracker\Fields;

use MUtil\Lazy\Call;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelBridge\TableBridge;

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.4
 */
class FieldOverviewTableSnippet extends \Gems\Snippets\ModelTableSnippet
{
    protected function addBrowseTableColumns(TableBridge $bridge, DataReaderInterface $model)
    {
        $params = [
            'id1' => $bridge->getLazy('gr2o_patient_nr'),
            'id2' => $bridge->getLazy('gr2o_id_organization'),
        ];

        $href = new Call(function(string $routeName, array $params = []) {
            return $this->routeHelper->getRouteUrl($routeName, $params);
        }, ['respondent.show', $params]);

        if ($href) {
            $aElem = new \MUtil\Html\AElement($href);
            $aElem->setOnEmpty('');

            // Make sure org is known
            $model->get('gr2o_id_organization');

            $model->set('gr2o_patient_nr', 'itemDisplay', $aElem);
            $model->set('respondent_name', 'itemDisplay', $aElem);
        }
        
        parent::addBrowseTableColumns($bridge, $model);
    }
}
