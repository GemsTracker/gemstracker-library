<?php

namespace Gems\Snippets\Tracker\Fields;

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 */

use Zalt\Html\AElement;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelBridge\TableBridge;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @since      Class available since version 2.0
 */
class FieldOverviewTableSnippet extends \Gems\Snippets\ModelTableSnippet
{
    protected function addBrowseTableColumns(TableBridge $bridge, DataReaderInterface $model)
    {
        $params = [
            'id1' => $bridge->getLate('gr2o_patient_nr'),
            'id2' => $bridge->getLate('gr2o_id_organization'),
        ];

        $href = $this->menuHelper->getLateRouteUrl(
            'respondent.show',
            $params, 
            $bridge);

        if ($href) {
            $aElem = new AElement($href['url']);
            $aElem->setOnEmpty('');

            $metaModel = $model->getMetaModel();
            
            // Make sure org is known
            $metaModel->get('gr2o_id_organization');

            $metaModel->set('gr2o_patient_nr', 'itemDisplay', $aElem);
            $metaModel->set('respondent_name', 'itemDisplay', $aElem);
        }
        
        parent::addBrowseTableColumns($bridge, $model);
    }
}
