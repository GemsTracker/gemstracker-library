<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: FieldsTableSnippet.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Snippets\Tracker\Fields;

use Gems\Tracker\Model\FieldMaintenanceModel;


/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 9-sep-2015 18:56:47
 */
class FieldsTableSnippet extends \Gems_Snippets_ModelTableSnippetAbstract
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
     *
     * @var \Gems\Tracker\Model\RoundModel
     */
    protected $model;

    /**
     * Required: the engine of the current track
     *
     * @var \Gems_Tracker_Engine_TrackEngineInterface
     */
    protected $trackEngine;

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return $this->trackEngine instanceof \Gems_Tracker_Engine_TrackEngineInterface;
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        if (! $this->model instanceof FieldMaintenanceModel) {
            $this->model = $this->trackEngine->getFieldsMaintenanceModel(false, 'index');
        }

        return $this->model;
    }
}
