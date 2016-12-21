<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Michiel Rook
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 *
 * @author Michiel Rook
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.3
 */
class Gems_Default_RoleAction extends \Gems_Controller_ModelSnippetActionAbstract
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
     * @var array Mixed key => value array for snippet initialization
     */
    protected $autofilterParameters = array(
        'extraSort'   => array(
            'grl_name' => SORT_ASC,
            ),
        );

    /**
     * Tags for cache cleanup after changes, passed to snippets
     *
     * @var array
     */
    public $cacheTags = array('gems_acl', 'roles');

    /**
     * The parameters used for the create and edit actions.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $createEditParameters = array(
        'usedPrivileges' => 'getUsedPrivileges',
    );

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    protected $createEditSnippets = 'Role\\RoleEditFormSnippet';

    /**
     *
     * @var array
     */
    protected $usedPrivileges;

    /**
     * Helper function to show a table
     *
     * @param string $caption
     * @param array $data
     * @param boolean $nested
     */
    protected function _showTable($caption, $data, $nested = false)
    {
        $table = \MUtil_Html_TableElement::createArray($data, $caption, $nested);
        $table->class = 'browser table';
        $div = \MUtil_Html::create()->div(array('class' => 'table-container'));
        $div[] = $table;
        $this->html[] = $div;
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
        $model = new \MUtil_Model_TableModel('gems__roles');

        $model->set('grl_name', 'label', $this->_('Name'),
                'size', 15,
                'minlength', 4
                );
        $model->set('grl_description', 'label', $this->_('Description'),
                'size', 40);
        $model->set('grl_parents', 'label', $this->_('Parents'));

        $tpa = new \MUtil_Model_Type_ConcatenatedRow(',', ', ');
        $tpa->apply($model, 'grl_parents');
        $model->setOnLoad('grl_parents', array(\Gems_Roles::getInstance(), 'translateToRoleNames'));

        $model->set('grl_privileges', 'label', $this->_('Privileges'));
        $tpr = new \MUtil_Model_Type_ConcatenatedRow(',', '<br/>');
        $tpr->apply($model, 'grl_privileges');

        if ($detailed) {
            $model->set('grl_name',
                    'validators[unique]', $model->createUniqueValidator('grl_name'),
                    'validators[nomaster]', new \MUtil_Validate_IsNot(
                            'master',
                            $this->_('The name "master" is reserved')
                            )
                    );

            $model->set('grl_privileges', 'formatFunction', array($this, 'formatPrivileges'));

            if ('show' === $action) {
                $model->addColumn('grl_parents', 'inherited');
                $tpa->apply($model, 'inherited');
                $model->set('inherited',
                        'label', $this->_('Inherited privileges'),
                        'formatFunction', array($this, 'formatInherited'));
                $model->setOnLoad('inherited', array(\Gems_Roles::getInstance(), 'translateToRoleNames'));

                // Concatenated field, we can not use onload so handle transaltion to rolenames in the formatFunction
                $model->addColumn("CONCAT(COALESCE(grl_parents, ''), '\t', COALESCE(grl_privileges, ''))", 'not_allowed');
                $model->set('not_allowed',
                        'label', $this->_('Not allowed'),
                        'formatFunction', array($this, 'formatNotAllowed'));
            }
        } else {
            $model->set('grl_privileges', 'formatFunction', array($this, 'formatLongLine'));
        }

        \Gems_Model::setChangeFieldsByPrefix($model, 'grl');

        return $model;
    }

    /**
     * Action for showing a edit item page with extra title
     */
    public function editAction()
    {
        $model   = $this->getModel();
        $data    = $model->loadFirst();

        //If we try to edit master, add an error message and reroute
        if (isset($data['grl_name']) && $data['grl_name']=='master') {
            $this->addMessage($this->_('Editing `master` is not allowed'));
            $this->_reroute(array('action'=>'index'), true);
        }

        parent::editAction();
    }

    /**
     * Output for browsing rols
     *
     * @param array $privileges
     * @return array
     */
    public function formatLongLine(array $privileges)
    {
        $output     = \MUtil_Html::create('div');

        if (count($privileges)) {
            $privileges = array_combine($privileges, $privileges);
            foreach ($this->getUsedPrivileges() as $privilege => $description) {
                if (isset($privileges[$privilege])) {
                    if (count($output) > 11) {
                        $output->append('...');
                        return $output;
                    }
                    if (\MUtil_String::contains($description, '<br/>')) {
                        $description = substr($description, 0, strpos($description, '<br/>') - 1);
                    }
                    $output->raw($description);
                    $output->br();
                }
            }
        }

        return $output;
    }

    /**
     * Output of not allowed for viewing rols
     *
     * @param array $parent
     * @return \MUtil_Html_ListElement
     */
    public function formatInherited(array $parents)
    {
        $privileges = array_keys($this->getInheritedPrivileges($parents));
        return $this->formatPrivileges($privileges);
    }

    /**
     * Output of not allowed for viewing rols
     *
     * @param strong $data parents tab privileges
     * @return \MUtil_Html_ListElement
     */
    public function formatNotAllowed($data)
    {
        list($parents_string, $privileges_string) = explode("\t", $data, 2);
        $parents    = explode(',', $parents_string);
        $privileges = explode(',', $privileges_string);
        if (count($privileges) > 0 ) {
            $privileges = array_combine($privileges, $privileges);
        }

        // Concatenated field, we can not use onload so handle translation here
        $parents = \Gems_Roles::getInstance()->translateToRoleNames($parents);

        $notAllowed = $this->getUsedPrivileges();
        $notAllowed = array_diff_key($notAllowed, $this->getInheritedPrivileges($parents), $privileges);

        $output = $this->formatPrivileges(array_keys($notAllowed));
        $output->class = 'notallowed deleted';

        return $output;
    }

    /**
     * Output for viewing rols
     *
     * @param array $privileges
     * @return \MUtil_Html_ListElement
     */
    public function formatPrivileges(array $privileges)
    {
        if (count($privileges)) {
            $output     = \MUtil_Html_ListElement::ul();
            $privileges = array_combine($privileges, $privileges);

            $output->class = 'allowed';

            foreach ($this->getUsedPrivileges() as $privilege => $description) {
                if (isset($privileges[$privilege])) {
                    $output->li()->raw($description);
                }
            }
            if (count($output)) {
                return $output;
            }
        }

        return \MUtil_Html::create('em', $this->_('No privileges found.'));
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Administrative roles');
    }

    /**
     * Get the privileges for thess parents
     *
     * @param array $parents
     * @return array privilege => setting
     */
    protected function getInheritedPrivileges(array $parents)
    {
        if (! $parents) {
            return array();
        }

        $rolePrivileges = $this->acl->getRolePrivileges();
        $inherited      = array();
        foreach ($parents as $parent) {
            if (isset($rolePrivileges[$parent])) {
                $inherited = $inherited + array_flip($rolePrivileges[$parent][\Zend_Acl::TYPE_ALLOW]);
                $inherited = $inherited +
                        array_flip($rolePrivileges[$parent][\MUtil_Acl::INHERITED][\Zend_Acl::TYPE_ALLOW]);
            }
        }
        // Sneaks in:
        unset($inherited[""]);

        return $inherited;
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
     * Action to shw overview of all privileges
     */
    public function overviewAction()
    {
        $roles = array();

        foreach ($this->acl->getRolePrivileges() as $role => $privileges) {
            $roles[$role][$this->_('Role')]    = $role;
            $roles[$role][$this->_('Parents')] = $privileges[\MUtil_Acl::PARENTS]   ? implode(', ', $privileges[\MUtil_Acl::PARENTS])   : null;
            $roles[$role][$this->_('Allowed')] = $privileges[\Zend_Acl::TYPE_ALLOW] ? implode(', ', $privileges[\Zend_Acl::TYPE_ALLOW]) : null;
            //$roles[$role][$this->_('Denied')]  = $privileges[\Zend_Acl::TYPE_DENY]  ? implode(', ', $privileges[\Zend_Acl::TYPE_DENY])  : null;
            $roles[$role][$this->_('Inherited')] = $privileges[\MUtil_Acl::INHERITED][\Zend_Acl::TYPE_ALLOW] ? implode(', ', $privileges[\MUtil_Acl::INHERITED][\Zend_Acl::TYPE_ALLOW]) : null;
            //$roles[$role][$this->_('Parent denied')]  = $privileges[\MUtil_Acl::INHERITED][\Zend_Acl::TYPE_DENY]  ? implode(', ', $privileges[\MUtil_Acl::INHERITED][\Zend_Acl::TYPE_DENY])  : null;
        }
        ksort($roles);

        $this->html->h2($this->_('Project role overview'));

        $this->_showTable($this->_('Roles'), $roles, true);
    }

    /**
     * Action to show all privileges
     */
    public function privilegeAction()
    {
        $privileges = array();

        foreach ($this->acl->getPrivilegeRoles() as $privilege => $roles) {
            $privileges[$privilege][$this->_('Privilege')] = $privilege;
            $privileges[$privilege][$this->_('Allowed')]   = $roles[\Zend_Acl::TYPE_ALLOW] ? implode(', ', $roles[\Zend_Acl::TYPE_ALLOW]) : null;
            $privileges[$privilege][$this->_('Denied')]    = $roles[\Zend_Acl::TYPE_DENY]  ? implode(', ', $roles[\Zend_Acl::TYPE_DENY])  : null;
        }

        // Add unassigned rights to the array too
        $all_existing = $this->getUsedPrivileges();
        $unassigned   = array_diff_key($all_existing, $privileges);
        $nonexistent  = array_diff_key($privileges, $all_existing);
        unset($nonexistent['pr.nologin']);
        unset($nonexistent['pr.islogin']);
        ksort($nonexistent);

        foreach ($unassigned as $privilege => $description) {
            $privileges[$privilege] = array(
                $this->_('Privilege') => $privilege,
                $this->_('Allowed')   => null,
                $this->_('Denied')    => null
            );
        }
        ksort($privileges);

        $this->html->h2($this->_('Project privileges'));
        $this->_showTable($this->_('Privileges'), $privileges, true);

        // Nonexistent rights are probably left-overs from old installations, this should be cleaned
        if (!empty($nonexistent)) {
            $this->_showTable($this->_('Assigned but nonexistent privileges'), $nonexistent, true);
        }
        // $this->acl->echoRules();
    }
}