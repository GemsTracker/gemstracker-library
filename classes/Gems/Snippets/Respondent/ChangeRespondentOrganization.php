<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Respondent;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.1 Oct 24, 2016 6:35:29 PM
 */
class ChangeRespondentOrganization extends \Gems_Snippets_ModelFormSnippetAbstract
{
    /**
     *
     * @var \Gems_AccessLog
     */
    protected $accesslog;

    /**
     *
     * @var int Number of records changed
     */
    protected $_changed = 0;

    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     * Required
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Only effective if copied
     *
     * @var boolean
     */
    protected $keepConsent = false;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \MUtil_Model_ModelAbstract
     */
    protected $model;

    /**
     *
     * @var \Gems_Tracker_Respondent
     */
    protected $respondent;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

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
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addFormElements(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $this->saveLabel = $this->_('Change organization');

        $choices = [
            'share' => $this->_('Share between both organizations, keep tracks in old'),
            'copy' => $this->_('Move tracks but keep in both organization'),
            'move' => $this->_('Move tracks and remove from old organization'),
        ];

        $bridge->addRadio('change_method',
                'label', $this->_('Change method'),
                'multiOptions', $choices,
                'onchange', 'this.form.submit();',
                'required', true
                );

        $sql  = "SELECT gr2o_id_organization, gr2o_patient_nr FROM gems__respondent2org WHERE gr2o_id_user = ?";

        $availableOrganizations = $this->util->getDbLookup()->getOrganizationsWithRespondents();
        $shareOption            = $this->formData['change_method'] == 'share';
        $disabled               = array();
        $existingOrgs           = $this->db->fetchPairs($sql, $this->respondent->getId());
        foreach ($availableOrganizations as $orgId => &$orgName) {
            $orglabel = \MUtil_Html::create('spaced');
            $orglabel->strong($orgName);
            if ($orgId == $this->formData['orig_org_id']) {
                $disabled[] = $orgId;
                $orglabel->em($this->_('Is current organization.'));

            } elseif (isset($existingOrgs[$orgId])) {
                $orglabel->em(sprintf(
                        $this->_('Exists already with respondent nr %s.'),
                        $existingOrgs[$orgId]
                        ));

                if ($shareOption) {      
                    // Only disable when we just share the patient and he already exists
                    $disabled[] = $orgId;
                }
            }

            $orgName = $orglabel->render($this->view);
        }
        
        $bridge->addRadio('gr2o_id_organization',
                'label', $this->_('New organization'),
                'disable', $disabled,
                'escape', false,
                'multiOptions', $availableOrganizations,
                'onchange', 'this.form.submit();',
                'validator', new \MUtil_Validate_IsNot(
                        $disabled,
                        $this->_('You cannot change to this organization')
                        )
                );
        
        if (in_array($this->formData['gr2o_id_organization'], $disabled)) {
            // Selected organization is now unavailable, reset selection
            $this->formData['gr2o_id_organization'] = null;
        }
        
        // Only allow to set a patient number when not exists in selected destination organization
        $disablePatientNumber = array_key_exists($this->formData['gr2o_id_organization'], $existingOrgs);
        if ($disablePatientNumber) {
            $this->formData['gr2o_patient_nr'] = $existingOrgs[$this->formData['gr2o_id_organization']];
        }
        $bridge->addText('gr2o_patient_nr', 'label', $this->_('New respondent nr'),
                'disabled', $disablePatientNumber ? 'disabled' : null
                );
    }

    /**
     * Hook that allows actions when data was saved
     *
     * When not rerouted, the form will be populated afterwards
     *
     * @param int $changed The number of changed rows (0 or 1 usually, but can be more)
     */
    protected function afterSave($changed)
    {
        // Communicate to user
        if ($changed >= 0) {
            switch ($this->formData['change_method']) {
                case 'share':
                    $message = $this->_('Shared %s with %s as %s');
                    break;

                case 'copy':
                    $message = $this->_('Moved %s with tracks to %s as %s');
                    break;
                case 'move':
                    $message = $this->_('Moved %s to %s as %s');
                    break;
                default:

            }
            $this->addMessage(sprintf(
                    $message,
                    $this->request->getParam(\MUtil_Model::REQUEST_ID1),
                    $this->loader->getOrganization($this->formData['gr2o_id_organization'])->getName(),
                    $this->formData['gr2o_patient_nr']
                    ));

            $this->accesslog->logChange($this->request, null, $this->formData);

        } else {
            parent::afterSave($changed);
        }
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        if ($this->model instanceof \Gems_Model_RespondentModel) {
            $model = $this->model;

        } else {
            if ($this->respondent instanceof \Gems_Tracker_Respondent) {
                $model = $this->respondent->getRespondentModel();

            } else {
                $model = $this->loader->getModels()->getRespondentModel(true);
            }
            $model->applyDetailSettings();
        }

        return $model;
    }

    /**
     * Retrieve the header title to display
     *
     * @return string
     */
    protected function getTitle()
    {
        if ($this->respondent instanceof \Gems_Tracker_Respondent) {
            if ($this->currentUser->areAllFieldsMaskedWhole('grs_first_name', 'grs_surname_prefix', 'grs_last_name')) {
                return sprintf(
                        $this->_('Change organization of respondent nr %s'),
                        $this->respondent->getPatientNumber()
                        );
            }
            return sprintf(
                    $this->_('Change organization of respondent nr %s: %s'),
                    $this->respondent->getPatientNumber(),
                    $this->respondent->getName()
                    );
        }
        return $this->_('Change organization of respondent');
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData()
    {
        parent::loadFormData();

        if (! isset($this->formData['change_method'])) {
            $this->formData['change_method'] = null;
        }
        $this->formData['orig_org_id'] = $this->request->getParam(\MUtil_Model::REQUEST_ID2);
    }

    /**
     * Hook containing the actual save code.
     *
     * Calls afterSave() for user interaction.
     *
     * @see afterSave()
     */
    protected function saveData()
    {
        $this->beforeSave();

        $fromOrgId   = $this->request->getParam(\MUtil_Model::REQUEST_ID2);
        $fromPid     = $this->request->getParam(\MUtil_Model::REQUEST_ID1);
        $fromRespId  = $this->respondent->getId();
        $toOrgId     = $this->formData['gr2o_id_organization'];
        $toPatientId = $this->formData['gr2o_patient_nr'];

        switch ($this->formData['change_method']) {
            case 'share':
                $this->_changed = $this->saveShare($fromOrgId, $fromPid, $toOrgId, $toPatientId);
                break;

            case 'copy':
                $this->_changed = $this->saveShare($fromOrgId, $fromPid, $toOrgId, $toPatientId);
                if ($this->_changed >= 0) {
                    $this->_changed += $this->saveMoveTracks($fromOrgId, $fromRespId, $toOrgId, $toPatientId, false);
                } else {
                    $this->addMessage($this->_('ERROR: Tracks not moved!'));
                }
                break;

            case 'move':
                $this->_changed = $this->saveTo($fromOrgId, $fromPid, $toOrgId, $toPatientId);
                if ($this->_changed >= 0) {
                    $this->_changed += $this->saveMoveTracks($fromOrgId, $fromRespId, $toOrgId, $toPatientId, true);
                } else {
                    $this->addMessage($this->_('ERROR: Tracks not moved!'));
                }
                break;

            default:
                $this->_changed = 0;

        }

        // Message the save
        $this->afterSave($this->_changed);
    }

    /**
     *
     * @param int $fromOrgId
     * @param int $fromRespId
     * @param int $toOrgId
     * @param string $toPatientId
     * @param boolean $all When true log data and appointments are also moved
     * @return int 1 If saved
     */
    protected function saveMoveTracks($fromOrgId, $fromRespId, $toOrgId, $toPatientId, $all = false)
    {
        $tables = array(
            'gems__respondent2track'              => ['gr2t_id_user',      'gr2t_id_organization', true],
            'gems__tokens'                        => ['gto_id_respondent', 'gto_id_organization',  true],
            'gems__appointments'                  => ['gap_id_user',       'gap_id_organization',  $all],
            'gems__log_respondent_communications' => ['grco_id_to',        'grco_organization',  $all],
        );

        $changed   = 0;
        $currentTs = new \MUtil_Db_Expr_CurrentTimestamp();
        $userId    = $this->currentUser->getUserId();

        foreach ($tables as $tableName => $settings) {
            list($respIdField, $orgIdField, $change) = $settings;

            if ($change) {
                $start = \MUtil_String::beforeChars($respIdField, '_');

                $values = [
                    $orgIdField            => $toOrgId,
                    $start . '_changed'    => $currentTs,
                    $start . '_changed_by' => $userId,
                    ];
                $where = [
                    "$respIdField = ?" => $fromRespId,
                    "$orgIdField = ?" => $fromOrgId,
                    ];
                $changed += $this->db->update($tableName, $values, $where);
            }
        }

        return $changed;
    }

    /**
     * Copy the respondent
     * 
     * @param int $fromOrgId
     * @param int $fromPatientId
     * @param int $toOrgId
     * @param string $toPatientId
     * @return int 1 If saved
     */
    protected function saveShare($fromOrgId, $fromPatientId, $toOrgId, $toPatientId)
    {
        /** @var \Gems_Model_RespondentModel $model */
        $model = $this->getModel();
        try {
            $result = $model->copyToOrg($fromOrgId, $fromPatientId, $toOrgId, $toPatientId, $this->keepConsent);
        } catch (\Gems\Exception\RespondentAlreadyExists $exc) {
            $info = $exc->getInfo();
            switch ($info) {
                case \Gems\Exception\RespondentAlreadyExists::OTHERPID:
                    $result = -1;
                    break;
                
                case \Gems\Exception\RespondentAlreadyExists::OTHERUID:
                    $result = -2;
                    break;
                
                case \Gems\Exception\RespondentAlreadyExists::SAME:
                default:
                    // Do nothing, already exists
                    $result = 0;
                    break;
            }
                
            $this->addMessage($exc->getMessage());
            return $result;
        }

        return $model->getChanged();
    }

    /**
     * Move the respondent
     * 
     * @param int $fromOrgId
     * @param int $fromPatientId
     * @param int $toOrgId
     * @param string $toPatientId
     * @return int 1 If saved
     */
    protected function saveTo($fromOrgId, $fromPatientId, $toOrgId, $toPatientId)
    {
        /** @var \Gems_Model_RespondentModel $model */
        $model = $this->getModel();
        try {
            $model->move($fromOrgId, $fromPatientId, $toOrgId, $toPatientId);    
        } catch (\Gems\Exception\RespondentAlreadyExists $exc) {
            $info = $exc->getInfo();
            switch ($info) {
                case \Gems\Exception\RespondentAlreadyExists::OTHERPID:
                    $result = -1;
                    break;
                
                case \Gems\Exception\RespondentAlreadyExists::OTHERUID:
                    $result = -2;
                    break;
                
                case \Gems\Exception\RespondentAlreadyExists::SAME:
                default:
                    // Do nothing, already exists
                    $result = 0;
                    break;
            }
                
            $this->addMessage($exc->getMessage());
            return $result;
        }        
        
        return $model->getChanged();
    }

    /**
     * Set what to do when the form is 'finished'.
     *
     * @return \MUtil_Snippets_ModelFormSnippetAbstract (continuation pattern)
     */
    protected function setAfterSaveRoute()
    {
        $this->routeAction = 'show';

        if ($this->request->getActionName() !== $this->routeAction) {
            $this->afterSaveRouteUrl = array(
                $this->request->getControllerKey() => $this->request->getControllerName(),
                $this->request->getActionKey() => $this->routeAction,
                );

            if ($this->afterSaveRouteKeys) {
                $this->afterSaveRouteUrl[\MUtil_Model::REQUEST_ID1] = $this->formData['gr2o_patient_nr'];
                $this->afterSaveRouteUrl[\MUtil_Model::REQUEST_ID2] = $this->formData['gr2o_id_organization'];
            }
        }

        return $this;
    }
}
