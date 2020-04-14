<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\User;

use Gems\Snippets\FormSnippetAbstract;

/**
 *
 * @package    Gems
 * @subpackage Snippets\User
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.8 14-Apr-2020 17:22:44
 */
class EmbeddedUserTestUrlForm extends FormSnippetAbstract
{
    /**
     * Required
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Zend_Controller_Router_Route
     */
    protected $router;

    /**
     * The form Id used for the save button
     *
     * If empty save button is not added
     *
     * @var string
     */
    protected $saveButtonId = null;

    /**
     *
     * @var \Gems_User_User
     */
    protected $selectedUser;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Add the elements to the form
     *
     * @param \Zend_Form $form
     */
    protected function addFormElements(\Zend_Form $form)
    {
        $orgOptions = [
            'label'        => $this->_('Organization'),
            'multiOptions' => $this->selectedUser->getAllowedOrganizations(),
            'onchange'     => 'this.form.submit();',
            ];

        $orgSelect = $form->createElement('Select', 'org_id', $orgOptions);
        $form->addElement($orgSelect);

        $userOptions = [
            'label'        => $this->_('Staff'),
            'description'  => $this->_('The Staff User to login as.'),
            'multiOptions' => $this->getStaffUsers($this->formData['org_id']),
            'onchange'     => 'this.form.submit();',
            ];
        $userSelect = $form->createElement('Select', 'login_id', $userOptions);
        $form->addElement($userSelect);

        $pidOptions = [
            'label'        => $this->_('Respondent'),
            'description'  => $this->_('The respondent to login to.'),
            'multiOptions' => $this->getPatients($this->formData['org_id']),
            'onchange'     => 'this.form.submit();',
            ];
        $pidSelect = $form->createElement('Select', 'pid', $pidOptions);
        $form->addElement($pidSelect);

        $paramOptions = [
            'label'        => $this->_('Standard query'),
            'onchange'     => 'this.form.submit();',
            ];
        $paramCheck = $form->createElement('Checkbox', 'old_param', $paramOptions);
        $form->addElement($paramCheck);

        $urlOptions = [
            'label'       => $this->_('Example url'),
            'cols'        => 80,
            'description' => \MUtil_String::contains($this->formData['example_url'], '%7B') ?
                $this->_('Replace {} / %7B %7D fields!') :
                $this->_('Please open in private mode or in other browser.'),
            'rows'        => 5,
            ];

        $url = $form->createElement('Textarea', 'example_url', $urlOptions);
        $form->addElement($url);
    }

    /**
     *
     * @return array
     */
    public function buildExampleEmbedUrl()
    {

        $auth = $this->selectedUser->getSystemDeferredAuthenticator();

        $url['epd'] = $this->selectedUser->getLoginName();
        $url['org'] = $this->formData['org_id'];
        $url['usr'] = $this->formData['login_id'];
        $url['pid'] = $this->formData['pid'];
        $url['key'] = $auth->getExampleKey($this->selectedUser);

        $url_string = '';
        if ($this->formData['old_param']) {
            foreach ($url as $key => $val) {
                $url_string .= "&$key=" . urlencode($val);
            }
            $url_string[0] = '?';
        } else {
            foreach ($url as $key => $val) {
                $url_string .= "/$key/" . urlencode($val);
            }
        }

        $router = $this->getRouter();
        return $this->util->getCurrentURI('embed/login') . $url_string;
    }

    /**
     * Return the default values for the form
     *
     * @return array
     */
    protected function getDefaultFormValues()
    {
        return [
            'org_id'    => $this->selectedUser->getBaseOrganizationId(),
            'login_id'  => '{login_id}',
            'pid'       => '{patient_nr}',
            'old_param' => 0,
            ];
    }

    /**
     * overrule to add your own buttons.
     *
     * @return \Gems_Menu_MenuList
     */
    protected function getMenuList()
    {
        return null;
    }

    /**
     *
     * @param int $orgId
     * @return array login_id => login_id
     */
    public function getPatients($orgId)
    {
        $def = ['{patient_nr}' => '{patient_nr}'];
        $sql = "SELECT gr2o_patient_nr, gr2o_patient_nr
            FROM gems__respondent2org INNER JOIN gems__reception_codes ON gr2o_reception_code = grc_id_reception_code
            WHERE gr2o_id_organization = ? AND grc_success = 1";


        $patients = $this->db->fetchPairs($sql, $orgId);

        if ($patients) {
            return $def + $patients;
        }

        return $def;
    }

    /**
     *
     * @return \Zend_Controller_Router_Route
     */
    public function getRouter()
    {
        if (! $this->router) {
            $front = \Zend_Controller_Front::getInstance();
            $this->router = $front->getRouter();
        }

        return $this->router;
    }

    /**
     *
     * @param int $orgId
     * @return array login_id => login_id
     */
    public function getStaffUsers($orgId)
    {
        $def = ['{login_id}' => '{login_id}'];
        $sql = "SELECT gul_login, gul_login
            FROM gems__user_logins
            WHERE gul_id_organization = ? AND gul_user_class = ? AND gul_can_login = 1";


        $staff = $this->db->fetchPairs($sql, [$orgId, $this->selectedUser->getUserDefinitionClass()]);

        if ($staff) {
            return $def + $staff;
        }

        return $def;
    }

    /**
     * Retrieve the header title to display
     *
     * @return string
     */
    protected function getTitle()
    {
        return $this->_('Example login url generator');
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        if (($this->selectedUser instanceof \Gems_User_User) && $this->selectedUser->isEmbedded() && $this->selectedUser->isActive()) {
            return parent::hasHtmlOutput();
        }
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData()
    {
        parent::loadFormData();

        $this->formData['example_url'] = $this->buildExampleEmbedUrl();
    }
}


