<?php

/**
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * A layered login form, useful when organizations have some kind of
 * hierarchy
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.5
 */
class Gems_User_Form_LayeredLoginForm extends \Gems_User_Form_LoginForm
{
    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     * The field name for the top organization element.
     *
     * @var string
     */
    public $topOrganizationFieldName = 'toporganization';

    /**
     * The label for the top-organization element
     *
     * @var string
     */
    public $topOrganizationDescription = null;

    /**
     * The label for the child-organization element
     *
     * @var string
     */
    public $childOrganizationDescription = null;

    /**
     * Get the organization id that has been currently entered
     *
     * @return int
     */
    public function getActiveOrganizationId()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $orgId = parent::getActiveOrganizationId();
            $topId = $request->getParam($this->topOrganizationFieldName);

            $children = $this->getChildOrganizations($topId);
            if ($orgId && isset($children[$orgId])) {
                return $orgId;
            }

            return $topId;
        }
    }

    /**
     * Return array of organizations that are a child of the given parentId
     *
     * @param int $parentId
     * @return array
     */
    public function getChildOrganizations($parentId = null)
    {
        static $children;

        if (is_null($parentId)) {
            return array();
        }

        if (! isset($children[$parentId])) {
            $organizations = $this->db->fetchPairs(
                    'SELECT gor_id_organization, gor_name
                        FROM gems__organizations
                        WHERE gor_active=1 AND gor_has_login=1 AND
                            (gor_accessible_by LIKE ' . $this->db->quote('%:' . $parentId . ':%') . ' OR
                                gor_id_organization = ' . $this->db->quote($parentId) .  ')
                            ORDER BY gor_name');

            natsort($organizations);

            $children[$parentId] = $organizations;
        }

        return $children[$parentId];
    }

    /**
     * Returns the organization id that should currently be used for this form.
     *
     * @return int Returns the current organization id, if any
     */
    public function getCurrentOrganizationId()
    {
        $userLoader = $this->loader->getUserLoader();

        if ($this->getElement($this->organizationFieldName) instanceof \Zend_Form_Element_Hidden) {
            // Url determines organization first when there is only one organization
            // and thus the element is of class hidden
            if ($orgId = $userLoader->getOrganizationIdByUrl()) {
                $this->_organizationFromUrl = true;
                $userLoader->getCurrentUser()->setCurrentOrganization($orgId);
                return $orgId;
            }
        }

        $request = $this->getRequest();
        if ($request->isPost() && ($orgId = $request->getParam($this->organizationFieldName))) {
            return $orgId;
        }

        $curOrg = $userLoader->getCurrentUser()->getCurrentOrganizationId();
        $orgs   = $this->getChildOrganizations($this->getCurrentTopOrganizationId());
        if (isset($orgs[$curOrg])) {
            return $curOrg;
        }

        $orgIds = array_keys($orgs);
        $firstId = reset($orgIds);
        return $firstId;
    }

    /**
     * Returns the top organization id that should currently be used for this form.
     *
     * @return int Returns the current organization id, if any
     */
    public function getCurrentTopOrganizationId()
    {
        $userLoader = $this->loader->getUserLoader();

        // Url determines organization first.
        if ($orgId = $userLoader->getOrganizationIdByUrl()) {
            $this->_organizationFromUrl = true;
            return ' ';
        }

        $request = $this->getRequest();
        if ($request->isPost() && ($orgId = $request->getParam($this->topOrganizationFieldName))) {
            \Gems_Cookies::set('gems_toporganization', $orgId);
            return $orgId;
        } else {
            $orgs = array_keys($this->getTopOrganizations());
            $firstId = reset($orgs);
            return \Gems_Cookies::get($this->getRequest(), 'gems_toporganization', $firstId);
        }
    }

    /**
     * Returns/sets an element for determining / selecting the organization.
     *
     * Depends on the top-organization
     *
     * @return \Zend_Form_Element_Xhtml
     */
    public function getOrganizationElement()
    {
        $element   = $this->getElement($this->organizationFieldName);
        $orgId     = $this->getCurrentOrganizationId();
        $parentId  = $this->getParentId();
        $childOrgs = $this->getChildOrganizations($parentId);

        if (!empty($childOrgs)) {
            if (count($childOrgs) == 1) {
                $element = new \Zend_Form_Element_Hidden($this->organizationFieldName);
                $this->addElement($element);

                if (! $this->_organizationFromUrl) {
                    $orgIds = array_keys($childOrgs);
                    $orgId  = reset($orgIds);
                }

                $element->setValue($orgId);
                $this->getRequest()->setPost($this->organizationFieldName, $orgId);

            } else {
                $element = new \Zend_Form_Element_Select($this->organizationFieldName);
                $element->setLabel($this->childOrganizationDescription);
                $element->setRegisterInArrayValidator(true);
                $element->setRequired(true);
                $element->setMultiOptions($childOrgs);

                if ($this->organizationMaxLines > 1) {
                    $element->setAttrib('size', min(count($childOrgs) + 1, $this->organizationMaxLines));
                }
                $this->addElement($element);
                $element->setValue($orgId);
            }
            $this->addElement($element);
        }

        return $element;
    }

    public function getParentId()
    {
        if ($this->_organizationFromUrl) {
            $userLoader = $this->loader->getUserLoader();

            return $userLoader->getOrganizationIdByUrl();
        }

        return $this->getElement($this->topOrganizationFieldName)->getValue();
    }

    /**
     * Return a list of organizations that are considered top-organizations, in this
     * case organizations that are not accessible by others as they are considered
     * the children of the top organizations. Feel free to modify to suit your
     * needs.
     *
     * @return array
     */
    public function getTopOrganizations()
    {
        try {
            $organizations = $this->db->fetchPairs('SELECT gor_id_organization, gor_name FROM gems__organizations WHERE gor_active=1 AND gor_has_login=1 AND (gor_accessible_by IS NULL OR gor_accessible_by = "::") ORDER BY gor_name');
        } catch (\Exception $e) {
            try {
                // 1.4 fallback
                $organizations = $this->db->fetchPairs('SELECT gor_id_organization, gor_name FROM gems__organizations WHERE gor_active=1 AND gor_accessible_by IS NULL ORDER BY gor_name');
            } catch (\Exception $e) {
                $organizations = array();
            }
        }

        natsort($organizations);

        return $organizations;
    }


    /**
     * Returns/sets an element for determining / selecting the top-organization.
     *
     * @return null|\Zend_Form_Element_Select
     */
    public function getTopOrganizationElement()
    {
        $element = $this->getElement($this->topOrganizationFieldName);
        $orgId   = $this->getCurrentTopOrganizationId();
        $orgs    = $this->getTopOrganizations();
        $hidden  = $this->_organizationFromUrl || (count($orgs) < 2);

        if ($this->_organizationFromUrl) {
            return null;
        }

        if ($hidden) {
            if (! $element instanceof \Zend_Form_Element_Hidden) {
                $element = new \Zend_Form_Element_Hidden($this->topOrganizationFieldName);
                $this->addElement($element);
            }

            $orgIds = array_keys($orgs);
            $orgId  = reset($orgIds);

            $element->setValue($orgId);

        } elseif (! $element instanceof \Zend_Form_Element_Select) {
            $element = new \Zend_Form_Element_Select($this->topOrganizationFieldName);
            $element->setLabel($this->topOrganizationDescription);
            $element->setRegisterInArrayValidator(true);
            $element->setRequired(true);
            $element->setMultiOptions($orgs);
            $element->setAttrib('onchange', 'this.form.submit();');

            if ($this->organizationMaxLines > 1) {
                $element->setAttrib('size', min(count($orgs) + 1, $this->organizationMaxLines));
            }
            $element->setValue($orgId);
        }
        $this->addElement($element);

        return $element;
    }

    /**
     * Load the elements, starting with the extra top organization element and
     * continue with the other elements like in the standard login form
     *
     * @return \Gems_User_Form_LayeredLoginForm
     */
    public function loadDefaultElements()
    {
        // If not already set, set some defaults for organization elements
        if (is_null($this->topOrganizationDescription)) {
            $this->topOrganizationDescription = $this->translate->_('Organization');
        }

        if (is_null($this->childOrganizationDescription)) {
            $this->childOrganizationDescription = $this->translate->_('Department');
        }

        $this->getTopOrganizationElement();

        parent::loadDefaultElements();

        return $this;
    }

    /**
     * Set the label for the child organization element
     *
     * Enables loading of parameter through \Zend_Form::__construct()
     *
     * @param string $description
     * @return \Gems_User_Form_LayeredLoginForm (continuation pattern)
     */
    public function setChildOrganizationDescription($description = null)
    {
        $this->childOrganizationDescription = $description;

        return $this;
    }

    /**
     * Set the label for the top organization element
     *
     * Enables loading of parameter through \Zend_Form::__construct()
     *
     * @param string $description
     * @return \Gems_User_Form_LayeredLoginForm (continuation pattern)
     */
    public function setTopOrganizationDescription($description = null)
    {
        $this->topOrganizationDescription = $description;

        return $this;
    }
}