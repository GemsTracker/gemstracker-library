<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker\Fields;

use Gems\Tracker\Model\FieldMaintenanceModel;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;


/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 9-sep-2015 18:56:47
 */
class FieldsTableSnippet extends \Gems\Snippets\ModelTableSnippetAbstract
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = array('gtf_id_order' => SORT_ASC);

    /**
     * The default controller for menu actions, if null the current controller is used.
     *
     * @var array (int/controller => action)
     */
    public $menuActionController = 'track-fields';

    /**
     * Menu actions to show in Edit box.
     *
     * If controller is numeric $menuActionController is used, otherwise
     * the key specifies the controller.
     *
     * @var array (int/controller => action)
     */
    public array $menuEditRoutes = ['track-builder.track-maintenance.track-fields.edit'];

    /**
     * Menu actions to show in Show box.
     *
     * If controller is numeric $menuActionController is used, otherwise
     * the key specifies the controller.
     *
     * @var array (int/controller => action)
     */
    public array $menuShowRoutes = ['track-builder.track-maintenance.track-fields.show'];

    /**
     *
     * @var FieldMaintenanceModel
     */
    protected $model;

    /**
     * Required: the engine of the current track
     *
     * @var \Gems\Tracker\Engine\TrackEngineInterface
     */
    protected $trackEngine;

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return bool False if required values are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return $this->trackEngine instanceof \Gems\Tracker\Engine\TrackEngineInterface;
    }

    protected function cleanUpFilter(array $filter, MetaModelInterface $metaModel): array
    {
        if (isset($filter['trackId'])) {
            $filter['gtf_id_track'] = $filter['trackId'];
            unset($filter['trackId']);
        } elseif ($this->requestInfo->getParam('trackId')) {
            $filter['gtf_id_track'] = $this->requestInfo->getParam('trackId');
        }
        return parent::cleanUpFilter($filter, $metaModel);
    }

    /**
     * Creates the model
     *
     * @return DataReaderInterface
     */
    protected function createModel(): DataReaderInterface
    {
        if (! $this->model instanceof FieldMaintenanceModel) {
            $this->model = $this->trackEngine->getFieldsMaintenanceModel(false, 'index');
        }

        return $this->model;
    }
    
    public function getRouteMaps(MetaModelInterface $metaModel): array
    {
        $output = parent::getRouteMaps($metaModel);
        $output['trackId'] = 'gtf_id_track';
        return $output;
    }
}
