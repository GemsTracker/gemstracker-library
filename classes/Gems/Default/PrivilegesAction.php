<?php

class Gems_Default_PrivilegesAction extends \Gems_Controller_ModelSnippetActionAbstract
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

    /**
     * The snippets used for the index action, after those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStopSnippets = ['Generic\\CurrentSiblingsButtonRowSnippet'];

    /**
     * List of privileges, their menu options and which role is allowed or denied this specific privilege
     * @var array
     */
    protected $privileges;

    /**
     *
     * @var array list of privileges set in the menu
     */
    protected $usedPrivileges;

    /**
     * Initialize translate and html objects
     *
     * Called from {@link __construct()} as final step of object instantiation.
     *
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->privileges = $this->getPrivileges();

    }

    protected function createModel($detailed, $action)
    {
        $first = reset($this->privileges);


        $model = new Gems_Model_PlaceholderModel('privileges', array_keys($first), $this->privileges);
        $model->set('privilege',
            'label', $this->_('Privilege')
        );
        $model->set('menu',
            'label', $this->_('Menu'),
            'formatFunction', ['MUtil_Html_Raw', 'raw']
        );
        $model->set('allowed',
            'label', $this->_('Allowed'),
            'formatFunction', ['MUtil_Html_TableElement', 'createVar']
        );
        $model->set('denied',
            'label', $this->_('Denied'),
            'formatFunction', ['MUtil_Html_TableElement', 'createVar']
        );

        return $model;
    }

    public static function changeArrow($value)
    {
        return str_replace(['-&gt;', '<br/>&nbsp;'], [' -- ', '; '], $value);
    }

    /**
     * Get the model for export and have the option to change it before using for export
     * @return
     */
    protected function getExportModel()
    {
        $model = parent::getExportModel();
        $model->del('allowed', 'formatFunction');
        $model->del('denied', 'formatFunction');
        $model->set('menu', 'formatFunction', [$this, 'changeArrow']);

        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Project privileges');
    }

    /**
     * Get list of privileges, their menu options and which role is allowed or denied this specific privilege
     * 
     * @return array list of privileges
     */
    protected function getPrivileges()
    {
        if (!$this->privileges) {
            $privileges = [];

            $allExisting = $this->getUsedPrivileges();

            foreach ($this->acl->getPrivilegeRoles() as $privilege => $roles) {
                $privileges[$privilege]['privilege'] = $privilege;
                $privileges[$privilege]['menu'] = isset($allExisting[$privilege]) ? $allExisting[$privilege] : null;
                $privileges[$privilege]['allowed'] = $roles[\Zend_Acl::TYPE_ALLOW] ? implode(', ', $roles[\Zend_Acl::TYPE_ALLOW]) : null;
                $privileges[$privilege]['denied'] = $roles[\Zend_Acl::TYPE_DENY] ? implode(', ', $roles[\Zend_Acl::TYPE_DENY]) : null;
            }

            // Add unassigned rights to the array too

            $unassigned = array_diff_key($allExisting, $privileges);
            $nonexistent = array_diff_key($privileges, $allExisting);
            unset($nonexistent['pr.nologin']);
            unset($nonexistent['pr.islogin']);
            ksort($nonexistent);

            foreach ($unassigned as $privilege => $description) {
                $privileges[$privilege] = array(
                    'privilege' => $privilege,
                    'menu' => isset($allExisting[$privilege]) ? $allExisting[$privilege] : null,
                    'allowed' => null,
                    'denied' => null
                );
            }
            ksort($privileges);

            $this->setNonExistent($nonexistent);

            $this->privileges = $privileges;
        }
        return $this->privileges;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('privilege', 'privileges', $count);
    }

    /**
     * Get the privileges a role can have.
     *
     * @return array
     */
    protected function getUsedPrivileges()
    {
        if (! $this->usedPrivileges) {
            $privileges = $this->menu->getUsedPrivileges();

            asort($privileges);
            //don't allow to edit the pr.nologin and pr.islogin privilege
            unset($privileges['pr.nologin']);
            unset($privileges['pr.islogin']);

            $this->usedPrivileges = $privileges;
        }

        return $this->usedPrivileges;
    }

    /**
     * Set assigned but nonexistent privileges and a snippet at the bottom of the page
     * @param array $nonexistent list of assigned but nonexistent privileges
     */
    protected function setNonExistent($nonexistent)
    {
        if ($nonexistent) {
            $translatedNonexistent = [];
            foreach($nonexistent as $right=>$values) {
                foreach($values as $key=>$value) {
                    if ($key == 'menu') {
                        continue;
                    }
                    $translatedNonexistent[$right][$this->_(ucfirst($key))] = $value;
                }
            }

            $this->indexParameters['tableTitle'] = $this->_('Assigned but nonexistent privileges');
            $this->indexParameters['tableData'] = $translatedNonexistent;
            $this->indexParameters['tableNested'] = true;

            $this->indexStopSnippets[] = 'DataTableSnippet';
        }
    }
}