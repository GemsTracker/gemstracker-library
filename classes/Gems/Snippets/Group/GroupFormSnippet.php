<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Group;

/**
 *
 * @package    Gems
 * @subpackage ItemSnippets
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 24-sep-2014 17:41:20
 */
class GroupFormSnippet extends \Gems\Snippets\ModelFormSnippetGeneric
{
    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $model;

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel()
    {
        // Replace two checkboxes with on radio button control
        $this->model->set('ggp_staff_members', 'elementClass', 'Hidden');
        $this->model->setOnSave('ggp_staff_members', array($this, 'saveIsStaff'));
        $this->model->set('ggp_respondent_members', 'elementClass', 'Hidden');
        $this->model->setOnSave('ggp_respondent_members', array($this, 'saveIsRespondent'));

        $options = array(
            '1' => $this->model->get('ggp_staff_members', 'label'),
            '2' => $this->model->get('ggp_respondent_members', 'label')
            );
        $this->model->set('staff_respondent', 'label', $this->_('Can be assigned to'),
                'elementClass', 'Radio',
                'multiOptions', $options,
                'order', $this->model->getOrder('ggp_staff_members') + 1);
        $this->model->setOnLoad('staff_respondent', array($this, 'loadStaffRespondent'));

        return $this->model;
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil\Registry\TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        $this->loadFormData();

        if ($this->getRedirectRoute()) {
            return false;
        }

        return parent::hasHtmlOutput();
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData()
    {
        if (! $this->formData) {
            parent::loadFormData();

            $model     = $this->getModel();
            $roles     = $model->get('ggp_role', 'multiOptions');
            $userRoles = $this->currentUser->getAllowedRoles();

            // \MUtil\EchoOut\EchoOut::track($userRoles, $roles);
            // Make sure we get the roles as they are labeled
            foreach ($roles as $role => $label) {
                if (! isset($userRoles[$role])) {
                    unset($roles[$role]);
                }
            }

            if ($this->formData['ggp_role'] && (! isset($roles[$this->formData['ggp_role']]))) {
                if ($this->createData) {
                    $this->formData['ggp_role'] = reset($roles);
                } else {
                    $this->addMessage($this->_('You do not have sufficient privilege to edit this group.'));
                    $this->afterSaveRouteUrl = array($this->request->getActionKey() => 'show');
                    $this->resetRoute        = false;
                    return;
                }
            }
            $model->set('ggp_role', 'multiOptions', $roles);

            $this->menu->getParameterSource()->offsetSet('ggp_role', $this->formData['ggp_role']);
        }
    }

    /**
     * A ModelAbstract->setOnLoad() function that takes care of transforming a
     * dateformat read from the database to a \Zend_Date format
     *
     * If empty or \Zend_Db_Expression (after save) it will return just the value
     * currently there are no checks for a valid date format.
     *
     * @see \MUtil\Model\ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return \MUtil\Date|\Zend_Db_Expr|string
     */
    public function loadStaffRespondent($value, $isNew = false, $name = null, array $context = array(), $isPost = false)
    {
        if (! $isPost) {
            if (!isset($context['staff_respondent'])) {
                if (isset($context['ggp_staff_members']) && $context['ggp_staff_members'] == 1) {
                    return 1;
                } else if (isset($context['ggp_respondent_members']) && $context['ggp_respondent_members'] == 1) {
                    return 2;
                }
            }
        }

        return $value;
    }

    /**
     * A ModelAbstract->setOnSave() function that returns the input
     * date as a valid date.
     *
     * @see \MUtil\Model\ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return \Zend_Date
     */
    public function saveIsRespondent($value, $isNew = false, $name = null, array $context = array())
    {
        return (isset($context['staff_respondent']) && (2 == $context['staff_respondent'])) ? 1 : 0;
    }

    /**
     * A ModelAbstract->setOnSave() function that returns the input
     * date as a valid date.
     *
     * @see \MUtil\Model\ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return \Zend_Date
     */
    public function saveIsStaff($value, $isNew = false, $name = null, array $context = array())
    {
        return (isset($context['staff_respondent']) && (1 == $context['staff_respondent'])) ? 1 : 0;
    }
}
