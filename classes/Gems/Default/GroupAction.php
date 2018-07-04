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
        'columns'     => 'getBrowseColumns',
        'extraSort'   => [
            'ggp_name' => SORT_ASC,
            ],
        );

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
     *
     * @var \Gems_User_User
     */
    public $currentUser;

    /**
     * The snippets used for the delete action.
     *
     * @var mixed String or array of snippets name
     */
    protected $deleteSnippets = 'Group_GroupDeleteSnippet';

    /**
     *
     * @throws \Exception
     */
    public function changeUiAction()
    {
        $request = $this->getRequest();

        if (! $this->currentUser->hasPrivilege('pr.group.switch', false)) {
            $this->escort->setError(
                    $this->_('No access to page'),
                    403,
                    sprintf($this->_('Access to the %s/%s page is not allowed for your current group: %s.'),
                            $request->getControllerName(),
                            $request->getActionName(),
                            $this->currentUser->getGroup()->getName()),
                    true);
        }

        $group = strtolower($request->getParam('group'));
        $url   = base64_decode($request->getParam('current_uri'));

        if ((! $url) || ('/' !== $url[0])) {
            throw new \Exception($this->_('Illegal group redirect url.'));
        }

        // Throws exception on invalid value
        $this->currentUser->setGroupTemp(intval($group));

        if ($url) {
            $this->getResponse()->setRedirect($url);
        } else {
            $this->currentUser->gotoStartPage($this->menu, $request);
        }
        return;
    }

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
        $rolesObj = \Gems_Roles::getInstance();

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
        $model->setOnLoad('ggp_role', [$rolesObj, 'translateToRoleName']);
        $model->setOnSave('ggp_role', [$rolesObj, 'translateToRoleId']);

        $groups = $dbLookup->getGroups();
        unset($groups['']);
        $model->set('ggp_may_set_groups', 'label', $this->_('May set these groups'),
                'elementClass', 'MultiCheckbox',
                'multiOptions', $groups
                );
        $tpa = new \MUtil_Model_Type_ConcatenatedRow(',', ', ');
        $tpa->apply($model, 'ggp_may_set_groups');

        $model->set('ggp_default_group', 'label', $this->_('Default group'),
                'description', $this->_('Default group when creating new staff member'),
                'elementClass', 'Select',
                'multiOptions', $dbLookup->getGroups()
                );

        $yesNo = $this->util->getTranslated()->getYesNo();
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
                'elementClass', 'Textarea',
                'itemDisplay', [$this, 'ipWrap'],
                'rows', 4,
                'validator', new \Gems_Validate_IPRanges()
                );
        $model->setIfExists('ggp_2factor_status', 'label', $this->_('Two factor status'),
                'multiOptions', ['Optional', 'Required when not exempt']
                );
        $model->setIfExists('ggp_no_2factor_ip_ranges', 'label', $this->_('Two factor exempt IP Ranges'),
                'description', $this->_('Separate with | example: 10.0.0.0-10.0.0.255 (subnet masks are not supported)'),
                'default', '127.0.0.1|::1',
                'elementClass', 'Textarea',
                'itemDisplay', [$this, 'ipWrap'],
                'rows', 4,
                'validator', new \Gems_Validate_IPRanges()
                );

        $model->set('ggp_group_active', 'label', $this->_('Active'),
                'elementClass', 'Checkbox',
                'multiOptions', $yesNo
                );

        if ($detailed) {
            $html = \MUtil_Html::create()->h4($this->_('Screen settings'));
            $model->set('screensettings', 'label', ' ',
                    'default', $html,
                    'elementClass', 'Html',
                    'value', $html
                    );

            $screenLoader = $this->loader->getScreenLoader();
            $model->set('ggp_respondent_browse', 'label', $this->_('Respondent browse screen'),
                    'default', 'Gems\\Screens\\Respondent\\Browse\\ProjectDefaultBrowse',
                    'elementClass', 'Radio',
                    'multiOptions', $screenLoader->listRespondentBrowseScreens()
                    );
            $screenLoader = $this->loader->getScreenLoader();
            $model->set('ggp_respondent_edit', 'label', $this->_('Respondent edit screen'),
                    'default', 'Gems\\Screens\\Respondent\\Edit\\ProjectDefaultEdit',
                    'elementClass', 'Radio',
                    'multiOptions', $screenLoader->listRespondentEditScreens()
                    );
            $screenLoader = $this->loader->getScreenLoader();
            $model->set('ggp_respondent_show', 'label', $this->_('Respondent show screen'),
                    'default', 'Gems\\Screens\\Respondent\\Show\\GemsProjectDefaultShow',
                    'elementClass', 'Radio',
                    'multiOptions', $screenLoader->listRespondentShowScreens()
                    );

            $maskStore = $this->loader->getUserMaskStore();

            $maskStore->addMaskSettingsToModel($model, 'ggp_mask_settings');
        }

        \Gems_Model::setChangeFieldsByPrefix($model, 'ggp');

        return $model;
    }

    /**
     * Set column usage to use for the browser.
     *
     * Must be an array of arrays containing the input for TableBridge->setMultisort()
     *
     * @return array or false
     */
    public function getBrowseColumns()
    {
        $br = \MUtil_Html::create('br');
        return [
            ['ggp_name', $br, 'ggp_role'],
            ['ggp_description'],
            ['ggp_may_set_groups'],
            ['ggp_default_group', $br, 'ggp_group_active'],
            ['ggp_staff_members', $br, 'ggp_respondent_members'],
            ['ggp_allowed_ip_ranges'],
            ['ggp_2factor_status'],
            ['ggp_no_2factor_ip_ranges'],
            ];
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
     *
     * @param string $value
     * @return string
     */
    public function ipWrap($value)
    {
        return \MUtil_Lazy::call('str_replace', '|', ' | ', $value);
    }
}
