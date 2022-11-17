<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Role;

use Zalt\Model\Bridge\FormBridgeInterface;
use Zalt\Model\Data\FullDataInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 18-feb-2015 15:35:07
 */
class RoleEditFormSnippet extends \Gems\Snippets\ModelFormSnippetAbstract
{
    /**
     *
     * @var \MUtil\Acl
     */
    protected $acl;

    /**
     * As it is better for translation utilities to set the labels etc. translated,
     * the \MUtil default is to disable translation.
     *
     * However, this also disables the translation of validation messages, which we
     * cannot set translated. The \MUtil form is extended so it can make this switch.
     *
     * @var boolean True
     */
    protected bool $disableValidatorTranslation = true;

    /**
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $model;

    /**
     *
     * @var array
     */
    protected $usedPrivileges;

    /**
     *
     * @var \Zend_View
     */
    protected $view;

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil\Model\Bridge\FormBridgeInterface $bridge
     * @param \MUtil\Model\ModelAbstract $model
     */
    protected function addBridgeElements(FormBridgeInterface $bridge, FullDataInterface $model)
    {
        $bridge->addHidden('grl_id_role');
        $bridge->addText('grl_name');
        $bridge->addText('grl_description');

        $roles = $this->acl->getRoles();
        if ($roles) {
            $possibleParents = array_combine($roles, $roles);
        } else {
            $possibleParents = array();
        }
        if (isset($this->formData['grl_parents']) && $this->formData['grl_parents']) {
            $this->formData['grl_parents'] = array_combine($this->formData['grl_parents'], $this->formData['grl_parents']);
        } else {
            $this->formData['grl_parents'] = array();
        }

        // Don't allow master, nologin or itself as parents
        unset($possibleParents['master']);
        unset($possibleParents['nologin']);
        $disabled = array();

        if (isset($this->formData['grl_name'])) {
            foreach ($possibleParents as $parent) {
                if ($this->acl->hasRole($this->formData['grl_name']) && $this->acl->inheritsRole($parent, $this->formData['grl_name'])) {
                    $disabled[] = $parent;
                    $possibleParents[$parent] .= ' ' .
                            \MUtil\Html::create('small', $this->_('child of current role'), $this->view);
                    unset($this->formData['grl_parents'][$parent]);
                } else {
                    foreach ($this->formData['grl_parents'] as $p2) {
                        if ($this->acl->hasRole($p2) && $this->acl->inheritsRole($p2, $parent)) {
                            $disabled[] = $parent;
                            $possibleParents[$parent] .= ' ' . \MUtil\Html::create(
                                    'small',
                                    \MUtil\Html::raw(sprintf(
                                            $this->_('inherited from %s'),
                                            \MUtil\Html::create('em', $p2, $this->view)
                                            )),
                                    $this->view);
                            $this->formData['grl_parents'][$parent] = $parent;
                        }
                    }
                }
            }
            $disabled[] = $this->formData['grl_name'];
            if (isset($possibleParents[$this->formData['grl_name']])) {
                $possibleParents[$this->formData['grl_name']] .= ' ' .
                        \MUtil\Html::create('small', $this->_('this role'), $this->view);
            }
        }

        $bridge->addMultiCheckbox('grl_parents', 'multiOptions', $possibleParents,
                'disable', $disabled,
                'escape', false,
                'onchange', 'this.form.submit();',
                'required', false
                );

        $allPrivileges       = $this->usedPrivileges;
        $rolePrivileges      = $this->acl->getRolePrivileges();

        if (isset($this->formData['grl_parents']) && $this->formData['grl_parents']) {
            $inherited           = $this->getInheritedPrivileges($this->formData['grl_parents']);
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
                    'disabled', 'disabled'
                    );
            $checkbox->setAttrib('escape', false); //Don't use escaping, so the line breaks work
            $checkbox->setValue(array_keys($inheritedPrivileges)); //To check the boxes
        }

        $bridge->getForm()->setDisableTranslator(true);
    }

    /**
     * Perform some actions to the data before it is saved to the database
     */
    protected function beforeSave()
    {
        if (isset($this->formData['grl_parents']) && (! is_array($this->formData['grl_parents']))) {
            $this->formData['grl_parents'] = explode(',', $this->formData['grl_parents']);
        }
        if (isset($this->formData['grl_parents']) && is_array($this->formData['grl_parents'])) {
            $this->formData['grl_parents'] = implode(
                    ',',
                    \Gems\Roles::getInstance()->translateToRoleIds($this->formData['grl_parents'])
                    );
        }

        //Always add nologin privilege to 'nologin' role
        if (isset($this->formData['grl_name']) && $this->formData['grl_name'] == 'nologin') {
            $this->formData['grl_privileges'][] = 'pr.nologin';
        } elseif (isset($this->formData['grl_name']) && $this->formData['grl_name'] !== 'nologin') {
            //Assign islogin to all other roles
            $this->formData['grl_privileges'][] = 'pr.islogin';
        }

        if (isset($this->formData['grl_privileges'])) {
            $this->formData['grl_privileges'] = implode(',', $this->formData['grl_privileges']);
        }
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel(): FullDataInterface
    {
        return $this->model;
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
                        array_flip($rolePrivileges[$parent][\MUtil\Acl::INHERITED][\Zend_Acl::TYPE_ALLOW]);
            }
        }
        // Sneaks in:
        unset($inherited[""]);

        return $inherited;
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData(): array
    {
        // \MUtil\EchoOut\EchoOut::track(file_get_contents('php://input'));
        parent::loadFormData();
        // \MUtil\EchoOut\EchoOut::track($this->formData);

        if ($this->request->isPost()) {
            if (! $this->request->getParam('grl_parents')) {
                $this->formData['grl_parents'] = [];
            }
        }

        // Sometimes these settings sneek in when changing the parents of a role
        foreach(['pr.nologin', 'pr.islogin'] as $val) {
            $key = array_search($val, $this->formData['grl_privileges']);
            if (false !== $key) {
                unset($this->formData['grl_privileges'][$key]);
            }
        }
        return $this->formData;
    }
 }
