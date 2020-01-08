<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Michel Rooks <info@touchdownconsulting.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

use Gems\Tracker\Model\FieldMaintenanceModel;

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
class Gems_Default_TrackFieldsAction extends \Gems_Default_TrackMaintenanceWithEngineActionAbstract
{
    /**
     * The parameters used for the autofilter action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $autofilterParameters = array(
        'extraSort' => array('gtf_id_order' => SORT_ASC),
        );

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'Tracker\\Fields\\FieldsTableSnippet';

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public $cacheTags = array('track', 'tracks');

    /**
     * The parameters used for the edit actions, overrules any values in
     * $this->createEditParameters.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $createParameters = array(
        'formTitle' => 'getCreateTitle',
    );

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    protected $createEditSnippets = [
        'Tracker\\Fields\\FieldEditSnippet',
        'Agenda\\ApplyFiltersInformation',
        ];

    /**
     * The snippets used for the delete action.
     *
     * @var mixed String or array of snippets name
     */
    protected $deleteSnippets = 'Tracker\\Fields\\FieldDeleteSnippet';

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Tracker\\Fields\\FieldsTitleSnippet', 'Tracker_Fields_FieldsAutosearchForm');

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showSnippets = [
        'Generic\\ContentTitleSnippet',
        'ModelItemTableSnippetGeneric',
        'Agenda\\ApplyFiltersInformation'
        ];

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \Gems_Model_TrackModel
     */
    public function createModel($detailed, $action)
    {
        $engine = $this->getTrackEngine();
        $model  = $engine->getFieldsMaintenanceModel($detailed, $action);

        return $model;
    }

    /**
     * Helper function to get the question for the delete action.
     *
     * @return $string
     */
    public function getDeleteQuestion()
    {
        $field = $this->_getParam('fid');
        if (FieldMaintenanceModel::APPOINTMENTS_NAME === $this->_getParam('sub')) {
            $used  = $this->db->fetchOne(
                    "SELECT COUNT(*)
                        FROM gems__respondent2track2appointment
                        WHERE gr2t2a_id_app_field = ? AND gr2t2a_id_appointment IS NOT NULL",
                    $field
                    );
        } else {
            $used  = $this->db->fetchOne(
                    "SELECT COUNT(*)
                        FROM gems__respondent2track2field
                        WHERE gr2t2f_id_field = ? AND gr2t2f_value IS NOT NULL",
                    $field
                    );
        }

        if (! $used) {
            return $this->_('Do you want to delete this field?');
        }

        $this->addMessage(sprintf($this->plural(
                'This field will be deleted from %s assigned track.',
                'This field will be deleted from %s assigned tracks.',
                $used), $used));

        return sprintf($this->plural(
                'Do you want to delete this field and the value stored for the field?',
                'Do you want to delete this field and the %s values stored for the field?',
                $used), $used);
    }

    /**
     * Helper function to get the title for the create action.
     *
     * @return $string
     */
    public function getCreateTitle()
    {
        return sprintf(
                $this->_('New field for %s track...') ,
                $this->util->getTrackData()->getTrackTitle($this->_getIdParam())
                );
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return sprintf($this->_('Fields %s'), $this->util->getTrackData()->getTrackTitle($this->_getIdParam()));
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('field', 'fields', $count);
    }
}