<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class Gems_Default_GroupAction extends \Gems_Controller_ModelSnippetActionAbstract
{
    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public $cacheTags = array('group', 'groups');

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    protected $createEditSnippets = 'Group_GroupFormSnippet';

    /**
     * The snippets used for the delete action.
     *
     * @var mixed String or array of snippets name
     */
    protected $deleteSnippets = 'Group_GroupDeleteSnippet';

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil_Model_ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $model = new \MUtil_Model_TableModel('gems__groups');

        // Add id for excel export
        if ($action == 'export') {
            $model->set('ggp_id_group', 'label', 'id');
        }

        $model->set('ggp_name', 'label', $this->_('Name'), 'size', 15, 'minlength', 4, 'validator', $model->createUniqueValidator('ggp_name'));
        $model->set('ggp_description', 'label', $this->_('Description'), 'size', 40);
        $model->set('ggp_role', 'label', $this->_('Role'), 'multiOptions', $this->util->getDbLookup()->getRoles());

        $yesNo = $this->util->getTranslated()->getYesNo();
        $model->set('ggp_group_active', 'label', $this->_('Active'), 'multiOptions', $yesNo, 'elementClass', 'Checkbox');
        $model->set('ggp_staff_members', 'label', $this->_('Staff'), 'multiOptions', $yesNo, 'elementClass', 'Checkbox');
        $model->set('ggp_respondent_members', 'label', $this->_('Respondents'), 'multiOptions', $yesNo, 'elementClass', 'Checkbox');

        $model->set('ggp_allowed_ip_ranges',
                'label', $this->_('Allowed IP Ranges'),
                'description', $this->_('Separate with | example: 10.0.0.0-10.0.0.255 (subnet masks are not supported)'),
                'size', 50,
                'validator', new \Gems_Validate_IPRanges(),
                'maxlength', 500);

        \Gems_Model::setChangeFieldsByPrefix($model, 'ggp');

        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Administrative groups');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('group', 'groups', $count);
    }
}
