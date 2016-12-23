<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
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
        $dbLookup = $this->util->getDbLookup();

        $model = new \MUtil_Model_TableModel('gems__groups');

        // Add id for excel export
        if ($action == 'export') {
            $model->set('ggp_id_group', 'label', 'id');
        }

        $model->set('ggp_name', 'label', $this->_('Name'),
                'minlength', 4,
                'size', 15,
                'validator', $model->createUniqueValidator('ggp_name')
                );
        $model->set('ggp_description', 'label', $this->_('Description'),
                'size', 40
                );
        $model->set('ggp_role', 'label', $this->_('Role'),
                'multiOptions', $dbLookup->getRoles()
                );
        $model->setOnLoad('ggp_role', [$this, 'translateRoleToName']);
        $model->setOnSave('ggp_role', [$this, 'translateRoleToId']);

        $groups = $dbLookup->getGroups();
        unset($groups['']);
        $model->set('ggp_may_set_groups', 'label', $this->_('May set groups'),
                'elementClass', 'MultiCheckbox',
                'multiOptions', $groups
                );
        $tpa = new \MUtil_Model_Type_ConcatenatedRow(',', ', ');
        $tpa->apply($model, 'ggp_may_set_groups');

        $yesNo = $this->util->getTranslated()->getYesNo();
        $model->set('ggp_group_active', 'label', $this->_('Active'),
                'elementClass', 'Checkbox',
                'multiOptions', $yesNo
                );
        $model->set('ggp_staff_members', 'label', $this->_('Staff'),
                'elementClass', 'Checkbox',
                'multiOptions', $yesNo
                );
        $model->set('ggp_respondent_members', 'label', $this->_('Respondents'),
                'elementClass', 'Checkbox',
                'multiOptions', $yesNo
                );

        $model->set('ggp_allowed_ip_ranges', 'label', $this->_('Allowed IP Ranges'),
                'description', $this->_('Separate with | example: 10.0.0.0-10.0.0.255 (subnet masks are not supported)'),
                'maxlength', 500,
                'size', 50,
                'validator', new \Gems_Validate_IPRanges()
                );

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

    /**
     * Translate a string role name to int role id
     *
     * @param string $value
     * @return int
     */
    public function translateRoleToId($value)
    {
        if (is_int($value)) {
            return $value;
        }
        $results = \Gems_Roles::getInstance()->translateToRoleIds([$value]);

        return reset($results);
    }

    /**
     * Translate a array or string role named to array of int role id
     *
     * @param mixed $value
     * @return array of int
     */
    public function translateRoleToIds($value)
    {
        if (! is_array($value)) {
            $value = explode(',', $value);
        }
        return implode(',', \Gems_Roles::getInstance()->translateToRoleIds($value));
    }

    /**
     * Translate a int role id to string role name
     *
     * @param int $value
     * @return string
     */
    public function translateRoleToName($value)
    {
        if (! intval($value)) {
            return $value;
        }
        $results = \Gems_Roles::getInstance()->translateToRoleNames([$value]);

        return reset($results);
    }

    /**
     * Translate a int array of string of int role id's to array of string role names
     *
     * @param mixed $value
     * @return array of string
     */
    public function translateRoleToNames($value)
    {
        if (! is_array($value)) {
            $value = explode(',', $value);
        }
        return \Gems_Roles::getInstance()->translateToRoleNames($value);
    }
}
