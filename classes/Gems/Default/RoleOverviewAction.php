<?php

class Gems_Default_RoleOverviewAction extends \Gems_Controller_ModelSnippetActionAbstract
{
    /**
     *
     * @var \MUtil_Acl
     */
    public $acl;

    /**
     * The parameters used for the autofilter action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialisation
     */
    protected $autofilterParameters = [
        'columns' => 'getBrowseColumns',
        'browse'  => false,
        'sortableLinks' => false,
    ];

    protected function createModel($detailed, $action)
    {
        $roles = $this->getRoles();
        $first = reset($roles);


        $model = new Gems_Model_PlaceholderModel('privileges', array_keys($first), $roles);
        $model->set('role',
            'label', $this->_('Role')
        );
        $model->set('parents',
            'label', $this->_('Parents'),
            'formatFunction', ['MUtil_Html_TableElement', 'createVar']
        );
        $model->set('allowed',
            'label', $this->_('Allowed'),
            'formatFunction', ['MUtil_Html_TableElement', 'createVar']
        );
        $model->set('inherited',
            'label', $this->_('Inherited'),
            'formatFunction', ['MUtil_Html_TableElement', 'createVar']
        );

        return $model;
    }

    /**
     * Get the model for export and have the option to change it before using for export
     * @return
     */
    protected function getExportModel()
    {
        $model = parent::getExportModel();
        $model->del('parents', 'formatFunction');
        $model->del('allowed', 'formatFunction');
        $model->del('inherited', 'formatFunction');

        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Project role overview');
    }

    /**
     * Get list of privileges, their menu options and which role is allowed or denied this specific privilege
     * 
     * @return array list of roles
     */
    protected function getRoles()
    {
        $roles = [];

        foreach ($this->acl->getRolePrivileges() as $role => $privileges) {
            $roles[$role]['role']    = $role;
            $roles[$role]['parents'] = $privileges[\MUtil_Acl::PARENTS]   ? implode(', ', $privileges[\MUtil_Acl::PARENTS])   : null;
            $roles[$role]['allowed'] = $privileges[\Zend_Acl::TYPE_ALLOW] ? implode(', ', $privileges[\Zend_Acl::TYPE_ALLOW]) : null;
            //$roles[$role]['denied']  = $privileges[\Zend_Acl::TYPE_DENY]  ? implode(', ', $privileges[\Zend_Acl::TYPE_DENY])  : null;
            $roles[$role]['inherited'] = $privileges[\MUtil_Acl::INHERITED][\Zend_Acl::TYPE_ALLOW] ? implode(', ', $privileges[\MUtil_Acl::INHERITED][\Zend_Acl::TYPE_ALLOW]) : null;
            //$roles[$role]['parent-denied']  = $privileges[\MUtil_Acl::INHERITED][\Zend_Acl::TYPE_DENY]  ? implode(', ', $privileges[\MUtil_Acl::INHERITED][\Zend_Acl::TYPE_DENY])  : null;
        }
        ksort($roles);

        return $roles;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('role', 'roles', $count);
    }
}