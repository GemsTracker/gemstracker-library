<?php

/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Default
 * @author     Michiel Rook
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
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
class Gems_Default_RoleAction  extends Gems_Controller_BrowseEditAction
{
    /**
     *
     * @var MUtil_Acl
     */
    public $acl;

    /**
     * @var GemsEscort
     */
    public $escort;
    
    /**
     *
     * @var array
     */
    protected $usedPrivileges;

    protected function _showTable($caption, $data, $nested = false)
    {
        $table = MUtil_Html_TableElement::createArray($data, $caption, $nested);
        $table->class = 'browser';
        $this->html[] = $table;
    }

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_FormBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @param optional boolean $new
     * @return void|array When an array of new values is return, these are used to update the $data array in the calling function
     */
    protected function addFormElements(MUtil_Model_FormBridge $bridge, MUtil_Model_ModelAbstract $model, array $data, $new = false)
    {
        $bridge->addHidden('grl_id_role');
        $bridge->addText('grl_name', 'size', 15, 'minlength', 4, 'validator', $model->createUniqueValidator('grl_name'));
        $bridge->addText('grl_description', 'size', 40);

        $roles = $this->acl->getRoles();
        if ($roles) {
            $possibleParents = array_combine($roles, $roles);
        } else {
            $possibleParents = array();
        }
        if (isset($data['grl_parents']) && $data['grl_parents']) {
            $currentParents = array_combine($data['grl_parents'], $data['grl_parents']);
        } else {
            $currentParents = array();
        }

        // Don't allow master, nologin or itself as parents
        unset($possibleParents['master']);
        unset($possibleParents['nologin']);
        $disabled   = array();

        if (isset($data['grl_name'])) {
            foreach ($possibleParents as $parent) {
                if ($this->acl->hasRole($data['grl_name']) && $this->acl->inheritsRole($parent, $data['grl_name'])) {
                    $disabled[] = $parent;
                    $possibleParents[$parent] .= ' ' .
                            MUtil_Html::create('small', $this->_('child of current role'), $this->view);
                    unset($currentParents[$parent]);
                } else {
                    foreach ($currentParents as $p2) {
                        if ($this->acl->hasRole($p2) && $this->acl->inheritsRole($p2, $parent)) {
                            $disabled[] = $parent;
                            $possibleParents[$parent] .= ' ' . MUtil_Html::create(
                                    'small',
                                    MUtil_Html::raw(sprintf(
                                            $this->_('inherited from %s'),
                                            MUtil_Html::create('em', $p2, $this->view)
                                            )),
                                    $this->view);
                            $currentParents[$parent] = $parent;
                        }
                    }
                }
            }
            $disabled[] = $data['grl_name'];
            if (isset($possibleParents[$data['grl_name']])) {
                $possibleParents[$data['grl_name']] .= ' ' .
                        MUtil_Html::create('small', $this->_('this role'), $this->view);
            }
        }
        $bridge->addMultiCheckbox('grl_parents', 'multiOptions', $possibleParents,
                'disable', $disabled,
                'escape', false,
                'required', false,
                'onchange', 'this.form.submit();');

        $allPrivileges       = $this->getUsedPrivileges();
        $rolePrivileges      = $this->escort->acl->getRolePrivileges();

        if (isset($data['grl_parents']) && $data['grl_parents']) {
            $inherited           = $this->getInheritedPrivileges($data['grl_parents']);
            $privileges          = array_diff_key($allPrivileges, $inherited);
            $inheritedPrivileges = array_intersect_key($allPrivileges, $inherited);
        } else {
            $privileges          = $allPrivileges;
            $inheritedPrivileges = false;
        }
        $checkbox = $bridge->addMultiCheckbox('grl_privileges', 'multiOptions', $privileges, 'required', false);
        $checkbox->setAttrib('escape', false); //Don't use escaping, so the line breaks work

        if ($inheritedPrivileges) {
            $checkbox = $bridge->addMultiCheckbox(
                    'inherited',
                    'label', $this->_('Inherited'),
                    'multiOptions', $inheritedPrivileges,
                    'required', false,
                    'disabled', 'disabled');
            $checkbox->setAttrib('escape', false); //Don't use escaping, so the line breaks work
            $checkbox->setValue(array_keys($inheritedPrivileges)); //To check the boxes
        }

        return array('grl_parents' => $currentParents);
    }

    /**
     * @param array $data
     * @param bool  $isNew
     * @return array
     */
    public function afterFormLoad(array &$data, $isNew)
    {
        if (isset($data['grl_parents']) && (! is_array($data['grl_parents']))) {
            $data['grl_parents'] = explode(',', $data['grl_parents']);
        }

        if (isset($data['grl_privileges']) && (! is_array($data['grl_privileges']))) {
            $data['grl_privileges'] = explode(',', $data['grl_privileges']);
        }
    }

    /**
     * As the ACL might have to be updated, rebuild the acl
     *
     * @param array $data
     * @param type $isNew
     * @return type
     */
    public function afterSave(array $data, $isNew)
    {
        $roles = $this->loader->getRoles($this->escort);
        $roles->build();

        return true;
    }

    /**
     *
     * @param array $data The data that will be saved.
     * @param boolean $isNew
     * $param Zend_Form $form
     * @return array|null Returns null if save was already handled, the data otherwise.
     */
    public function beforeSave(array &$data, $isNew, Zend_Form $form = null)
    {
        if (isset($data['grl_parents'])) {
            $data['grl_parents'] = implode(',', $data['grl_parents']);
        }

        //Always add nologin privilege to 'nologin' role
        if (isset($data['grl_name']) && $data['grl_name'] == 'nologin') {
            $data['grl_privileges'][] = 'pr.nologin';
        } elseif (isset($data['grl_name']) && $data['grl_name'] !== 'nologin') {
            //Assign islogin to all other roles
            $data['grl_privileges'][] = 'pr.islogin';
        }

        if (isset($data['grl_privileges'])) {
            $data['grl_privileges'] = implode(',', $data['grl_privileges']);
        }

        if(isset($data['grl_name']) && $data['grl_name'] == 'master') {
            $form->getElement('grl_name')->setErrors(array($this->_('Illegal name')));
            return false;
        }

        return true;
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
     * @return MUtil_Model_ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $model = new MUtil_Model_TableModel('gems__roles');

        $model->set('grl_name', 'label', $this->_('Name'));
        $model->set('grl_description', 'label', $this->_('Description'));
        $model->set('grl_parents', 'label', $this->_('Parents'));

        $tpa = new MUtil_Model_Type_ConcatenatedRow(',', ', ');
        $tpa->apply($model, 'grl_parents');

        $model->set('grl_privileges', 'label', $this->_('Privileges'));
        $tpr = new MUtil_Model_Type_ConcatenatedRow(',', '<br/>');
        $tpr->apply($model, 'grl_privileges');
        
        if ($detailed) {
            $model->set('grl_privileges', 'formatFunction', array($this, 'formatPrivileges'));
            
            if ('show' === $action) {
                $model->addColumn('grl_parents', 'inherited');
                $tpa->apply($model, 'inherited');
                $model->set('inherited', 
                        'label', $this->_('Inherited privileges'),
                        'formatFunction', array($this, 'formatInherited'));
                
                $model->addColumn("CONCAT(COALESCE(grl_parents, ''), '\t', COALESCE(grl_privileges, ''))", 'not_allowed');
                $model->set('not_allowed', 
                        'label', $this->_('Not allowed'),
                        'formatFunction', array($this, 'formatNotAllowed'));
            }
        } else {
            $model->set('grl_privileges', 'formatFunction', array($this, 'formatLongLine'));
        }

        Gems_Model::setChangeFieldsByPrefix($model, 'grl');

        return $model;
    }

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
        $output     = MUtil_Html::create('div');
        
        if (count($privileges)) {       
            $privileges = array_combine($privileges, $privileges);
            foreach ($this->getUsedPrivileges() as $privilege => $description) {
                if (isset($privileges[$privilege])) {
                    if (count($output) > 11) {
                        $output->append('...');
                        return $output;
                    }
                    if (MUtil_String::contains($description, '<br/>')) {
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
     * @return MUtil_Html_ListElement
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
     * @return MUtil_Html_ListElement
     */
    public function formatNotAllowed($data)
    {
        list($parents_string, $privileges_string) = explode("\t", $data, 2);
        $parents    = explode(', ', $parents_string);
        $privileges = explode(', ', $privileges_string);
        if (count($privileges) > 0 ) {
            $privileges = array_combine($privileges, $privileges);
        }
        
        $notAllowed = $this->getUsedPrivileges();
        $notAllowed = array_diff_key($notAllowed, $this->getInheritedPrivileges($parents), $privileges);

        $output = $this->formatPrivileges(array_keys($notAllowed));
        $output->class = 'deleted';
                
        return $output;
    }
    
    /**
     * Output for viewing rols
     * 
     * @param array $privileges
     * @return MUtil_Html_ListElement
     */
    public function formatPrivileges(array $privileges)
    {
        if (count($privileges)) {
            $output     = MUtil_Html_ListElement::ul();
            $privileges = array_combine($privileges, $privileges);
            
            foreach ($this->getUsedPrivileges() as $privilege => $description) {
                if (isset($privileges[$privilege])) {
                    $output->li()->raw($description);
                }
            }
            if (count($output)) {
                return $output;
            }
        }
        
        return MUtil_Html::create('em', $this->_('No privileges found.'));
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

        $rolePrivileges = $this->escort->acl->getRolePrivileges();
        $inherited      = array();
        foreach ($parents as $parent) {
            if (isset($rolePrivileges[$parent])) {
                $inherited = $inherited + array_flip($rolePrivileges[$parent][Zend_Acl::TYPE_ALLOW]);
                $inherited = $inherited + 
                        array_flip($rolePrivileges[$parent][MUtil_Acl::INHERITED][Zend_Acl::TYPE_ALLOW]);
            }
        }
        // Sneaks in:
        unset($inherited[""]);
        
        return $inherited;
    }
    
    public function getTopic($count = 1)
    {
        return $this->plural('role', 'roles', $count);
    }

    public function getTopicTitle()
    {
        return $this->_('Administrative roles');
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

    public function overviewAction()
    {
        $roles = array();

        foreach ($this->acl->getRolePrivileges() as $role => $privileges) {
            $roles[$role][$this->_('Role')]    = $role;
            $roles[$role][$this->_('Parents')] = $privileges[MUtil_Acl::PARENTS]   ? implode(', ', $privileges[MUtil_Acl::PARENTS])   : null;
            $roles[$role][$this->_('Allowed')] = $privileges[Zend_Acl::TYPE_ALLOW] ? implode(', ', $privileges[Zend_Acl::TYPE_ALLOW]) : null;
            //$roles[$role][$this->_('Denied')]  = $privileges[Zend_Acl::TYPE_DENY]  ? implode(', ', $privileges[Zend_Acl::TYPE_DENY])  : null;
            $roles[$role][$this->_('Inherited')] = $privileges[MUtil_Acl::INHERITED][Zend_Acl::TYPE_ALLOW] ? implode(', ', $privileges[MUtil_Acl::INHERITED][Zend_Acl::TYPE_ALLOW]) : null;
            //$roles[$role][$this->_('Parent denied')]  = $privileges[MUtil_Acl::INHERITED][Zend_Acl::TYPE_DENY]  ? implode(', ', $privileges[MUtil_Acl::INHERITED][Zend_Acl::TYPE_DENY])  : null;
        }
        ksort($roles);

        $this->html->h2($this->_('Project role overview'));

        $this->_showTable($this->_('Roles'), $roles, true);
    }

    public function privilegeAction()
    {
        $privileges = array();

        foreach ($this->acl->getPrivilegeRoles() as $privilege => $roles) {
            $privileges[$privilege][$this->_('Privilege')] = $privilege;
            $privileges[$privilege][$this->_('Allowed')]   = $roles[Zend_Acl::TYPE_ALLOW] ? implode(', ', $roles[Zend_Acl::TYPE_ALLOW]) : null;
            $privileges[$privilege][$this->_('Denied')]    = $roles[Zend_Acl::TYPE_DENY]  ? implode(', ', $roles[Zend_Acl::TYPE_DENY])  : null;
        }
        ksort($privileges);

        $this->html->h2($this->_('Project privileges'));
        $this->_showTable($this->_('Privileges'), $privileges, true);

        // $this->acl->echoRules();
    }
}
