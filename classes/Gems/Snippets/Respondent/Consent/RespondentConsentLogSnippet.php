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
 * @since      Class available since version 1.8.6 11-Oct-2019 12:26:51
 */
class RespondentConsentLogSnippet extends \MUtil_Snippets_ModelTableSnippetAbstract
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = ['glrc_created' => SORT_DESC];

    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'browser table';

    /**
     *
     * @var \Gems_Model_RespondentModel
     */
    protected $model;

    /**
     * Optional
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
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        if ($this->respondent instanceof \Gems_Tracker_Respondent) {
            $this->caption = sprintf(
                    $this->_('Consent change log for respondent %s, %s at %s'),
                    $this->respondent->getPatientNumber(),
                    $this->respondent->getFullName(),
                    $this->respondent->getOrganization()->getName()
                    );

        }
        $this->onEmpty = $this->_('No consent changes found');
    }


    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        $respModel = $this->model;

        $fieldOptions = [];
        $valueOptions = [];
        foreach ($respModel->consentFields as $field) {
            $fieldOptions[$field] = $respModel->get($field, 'label');
            $options      = (array) $respModel->get($field, 'multiOptions');
            $valueOptions = array_merge($valueOptions, $options);
        }
        $orgs = $this->util->getDbLookup()->getOrganizations();
        foreach ($orgs as $id => $name) {
            $fieldOptions[$id] = sprintf('has access to %s', $name);
        }

        // \MUtil_Echo::track($fieldOptions, $valueOptions);

        $model = new \MUtil_Model_TableModel('gems__log_respondent_consents');

        $model->set('glrc_id_organization', 'label', $this->_('Organization'),
                'multiOptions', $orgs);
        $model->set('glrc_consent_field', 'label', $this->_('Type'),
                'multiOptions', $fieldOptions);
        $model->set('glrc_old_consent', 'label', $this->_('Previous consent'),
                'multiOptions', $valueOptions);
        $model->set('glrc_new_consent', 'label', $this->_('New consent'),
                'multiOptions', $valueOptions);
        $model->set('glrc_created', 'label', $this->_('Changed on'),
                'dateFormat', $respModel->get('gr2o_changed_by', 'dateFormat'),
                'formatFunction', $respModel->get('gr2o_changed_by', 'formatFunction'));
        $model->set('glrc_created_by', 'label', $this->_('Changed by'),
                'multiOptions', $respModel->get('gr2o_changed_by', 'multiOptions'));

        if ($this->respondent && $this->respondent->exists) {
            $model->addFilter([
                'glrc_id_user' => $this->respondent->getId(),
                [
                    'glrc_id_organization' => $this->respondent->getOrganizationId(),
                    'glrc_consent_field'   => $this->respondent->getOrganizationId(),
                    ],
            ]);
        }


        return $model;
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        return parent::hasHtmlOutput() &&
                ($this->respondent instanceof \Gems_Tracker_Respondent) &&
                $this->respondent->exists;
    }
}
