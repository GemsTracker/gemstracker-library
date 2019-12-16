<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent\Consent
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Respondent\Consent;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent\Consent
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 11-Oct-2019 15:36:16
 */
class RespondentConsentFormSnippet extends \Gems_Snippets_ModelFormSnippetAbstract
{
    /**
     *
     * @var array field_name => orgId
     */
    protected $_consentGrabs = [];

    /**
     *
     * @var array field_name => orgId
     */
    protected $_consentGrants = [];

    /**
     *
     * @var \MUtil_Model_TableModel
     */
    protected $_orgConModel;

    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var boolean Add gr2o_mailable to editable fields
     */
    protected $editMailable;

    /**
     *
     * @var array The fields to exhibit
     */
    protected $exhibit = [
        'gr2o_id_organization',
        'gr2o_patient_nr',
        'name',
        'gr2o_email',
        'grs_gender',
        'grs_birthday',
        ];

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems_Model_RespondentModel
     */
    protected $model;

    /**
     * Right to grab consent from other organizations
     *
     * @var boolean
     */
    protected $orgConsentGrab = false;

    /**
     * Right to grant consent to other organizations
     *
     * @var boolean
     */
    protected $orgConsentGrant = false;

    /**
     * Right to see consents from/to other organizations
     *
     * @var boolean
     */
    protected $orgConsentSee = false;

    /**
     * Currently stored consents
     *
     * @var array field => consent
     */
    protected $orgConsentStored = [];

    /**
     * When true a tabbed form is used.
     *
     * @var boolean
     */
    protected $useTabbedForm = false;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     *
     * @param string $name
     * @param string $label
     * @param string $description
     * @param string $defConsent
     * @param boolean $editable
     */
    protected function addOrgConField($name, $label, $description, $defConsent, $editable)
    {
        $this->model->set($name, 'label', $label,
                'default', $defConsent,
                'description', $description,
                'disabled', $editable ? null : 'disabled',
                'multiOptions', $this->util->getDbLookup()->getUserConsents(),
                'elementClass', 'Radio',
                'separator', ' ',
                'readonly', $editable ? null : 'readonly',
                'required', true
                );
    }

    /**
     *
     * @param string $name
     * @param string $label
     */
    protected function addOrgConHeader($name, $label)
    {
        $html = \MUtil_Html::create()->h3($label);
        $this->model->set($name, 'default', $html, 'label', ' ', 'elementClass', 'Html', 'value',  $html);
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * This function is no needed if the classes are setup correctly
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        if ($this->currentUser->hasPrivilege('pr.respondent.org2orgcon.grab')) {
            $this->orgConsentGrab = true;
        }
        if ($this->currentUser->hasPrivilege('pr.respondent.org2orgcon.grant')) {
            $this->orgConsentGrant = true;
        }
        if ($this->orgConsentGrab || $this->orgConsentGrant || $this->currentUser->hasPrivilege('pr.respondent.org2orgcon.see')) {
            $this->orgConsentSee = true;
        }
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        if (in_array('name', $this->exhibit)) {
            \Gems_Model_RespondentModel::addNameToModel($this->model, $this->_('Name'));
            $this->model->set('name', 'order', $this->model->getOrder('gr2o_patient_nr') + 1);
        }

        $all = $this->model->getCol('label');
        foreach ($all as $name => $label) {
            if (in_array($name, $this->model->consentFields)) {
                continue;
            }

            if ($this->editMailable && ('gr2o_mailable' == $name)) {
                continue;
            }
            if (in_array($name, $this->exhibit)) {
                $this->model->set($name, 'elementClass', 'Exhibitor');
            } else {
                $this->model->set($name, 'elementClass', 'None');
            }
        }

        return $this->model;
    }

    /**
     * Retrieve the header title to display
     *
     * @return string
     */
    protected function getTitle()
    {
        return $this->_('Consents');
    }

    /**
     * Helper function to allow generalized statements about the items in the model to used specific item names.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('consent', 'consents', $count);
    }

    protected function loadConsentData()
    {
        $currentOrgId  = $this->formData['gr2o_id_organization'];
        $currentOrg    = $this->loader->getOrganization($currentOrgId);
        $currentRespId = $this->formData['grs_id_user'];

        $defConsent = $this->model->get('gr2o_consent', 'default');
        if (! $defConsent) {
            $defConsent = $this->util->getDefaultConsent();
        }

        $patientOrgs = $this->db->fetchPairs(
                "SELECT gr2o_id_organization, gr2o_id_organization FROM gems__respondent2org WHERE gr2o_id_user = ?",
                $currentRespId);

        $shares = array_intersect_key($currentOrg->getSharingOrganizations(), $patientOrgs);
        if ($shares) {
            $this->addOrgConHeader('orgSharingToHeader',
                    sprintf($this->_('Consents given to %s for access to respondent %s'), $currentOrg->getName(), $this->formData['gr2o_patient_nr'])
                    );

            $sql = "SELECT gco2o_organization_from, gco2o_consent
                FROM gems__consent_org2org
                WHERE gco2o_id_user = ? AND gco2o_organization_to = ?";
            $storage = $this->db->fetchPairs($sql, [$currentRespId, $currentOrgId]);

            // \MUtil_Echo::track($shares, $storage, $defConsent);

            foreach ($shares as $orgId => $orgName) {
                $field = 'from_con_' . $orgId;
                $this->addOrgConField($field,
                        sprintf($this->_('Use data from %s'), $orgName),
                        sprintf($this->_('Can %s use data from %s.'), $currentOrg->getName(), $orgName),
                        $defConsent,
                        $this->orgConsentGrab
                        );

                $this->_consentGrabs[$field] = $orgId;
                $this->orgConsentStored[$field] = isset($storage[$orgId]) ? $storage[$orgId] : $defConsent;
                $this->formData[$field] = $this->request->getParam($field, $this->orgConsentStored[$field]);
            }
        }

        $shareIds = array_intersect($currentOrg->getShareableWithIds(), $patientOrgs);
        if ($shareIds) {
            $this->addOrgConHeader('orgSharingFromHeader',
                    sprintf($this->_('Consents granted by %s for access to respondent %s'), $currentOrg->getName(), $this->formData['gr2o_patient_nr'])
                    );

            $sql = "SELECT gco2o_organization_to, gco2o_consent
                FROM gems__consent_org2org
                WHERE gco2o_id_user = ? AND gco2o_organization_from = ?";
            $storage = $this->db->fetchPairs($sql, [$currentRespId, $currentOrgId]);

            // \MUtil_Echo::track($shareIds, $storage);

            foreach ($shareIds as $orgId) {
                $field = 'to_con_' . $orgId;
                $orgName = $this->loader->getOrganization($orgId)->getName();
                $this->addOrgConField($field,
                        sprintf($this->_('Share data with %s'), $orgName),
                        sprintf($this->_('Can %s use data from %s.'), $orgName, $currentOrg->getName()),
                        $defConsent,
                        $this->orgConsentGrant
                        );

                $this->_consentGrants[$field] = $orgId;
                $this->orgConsentStored[$field] = isset($storage[$orgId]) ? $storage[$orgId] : $defConsent;
                $this->formData[$field] = $this->request->getParam($field, $this->orgConsentStored[$field]);
            }
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

        if ($this->orgConsentSee) {
            // \MUtil_Echo::track($this->formData);
            $this->loadConsentData();
        }
    }

    /**
     * Hook containing the actual save code.
     *
     * Call's afterSave() for user interaction.
     *
     * @see afterSave()
     */
    protected function saveData()
    {
        $this->beforeSave();

        if ($this->csrfId && $this->_csrf) {
            unset($this->formData[$this->csrfId]);
        }

        // Perform the save
        $model          = $this->getModel();
        $this->formData = $model->save($this->formData);
        $changed        = $model->getChanged();

        if ($this->orgConsentGrab || $this->orgConsentGrant) {
            $this->_orgConModel = new \MUtil_Model_TableModel('gems__consent_org2org');
            \Gems_Model::setChangeFieldsByPrefix($this->_orgConModel, 'gco2o', $this->currentUser->getUserId());

            $this->saveOrgConsents();

            $changed += $this->_orgConModel->getChanged();
        }

        // Message the save
        $this->afterSave($changed);
    }

    protected function saveOrgConsentLogged(array $newValues, $oldConsent)
    {
        \MUtil_Echo::track($oldConsent, $newValues['gco2o_consent']);
        if ($oldConsent != $newValues['gco2o_consent']) {
            $this->_orgConModel->save($newValues);

            $values['glrc_id_user']         = $newValues['gco2o_id_user'];
            $values['glrc_id_organization'] = $newValues['gco2o_organization_to'];
            $values['glrc_consent_field']   = $newValues['gco2o_organization_from'];
            $values['glrc_old_consent']     = $oldConsent;
            $values['glrc_new_consent']     = $newValues['gco2o_consent'];
            $values['glrc_created']         = new \MUtil_Db_Expr_CurrentTimestamp();
            $values['glrc_created_by']      = $this->currentUser->getUserId();

            $this->db->insert('gems__log_respondent_consents', $values);
        }
    }

    protected function saveOrgConsents()
    {
        $currentOrgId   = $this->formData['gr2o_id_organization'];

        $values['gco2o_id_user'] = $this->formData['grs_id_user'];

        // \MUtil_Echo::track($this->request->getParams());

        if ($this->orgConsentGrab && $this->_consentGrabs) {
            $values['gco2o_organization_to'] = $currentOrgId;

            foreach ($this->_consentGrabs as $field => $orgId) {
                $values['gco2o_organization_from'] = $orgId;
                $values['gco2o_consent']           = $this->formData[$field];

                $this->saveOrgConsentLogged($values, $this->orgConsentStored[$field]);
                // \MUtil_Echo::track($values);
            }
        }
        if ($this->orgConsentGrant && $this->_consentGrants) {
            $values['gco2o_organization_from'] = $currentOrgId;

            foreach ($this->_consentGrants as $field => $orgId) {
                $values['gco2o_organization_to'] = $orgId;
                $values['gco2o_consent']         = $this->formData[$field];

                $this->saveOrgConsentLogged($values, $this->orgConsentStored[$field]);
                // \MUtil_Echo::track($values);
            }
        }

        \MUtil_Echo::track($this->orgConsentStored, $this->formData);
    }
}
