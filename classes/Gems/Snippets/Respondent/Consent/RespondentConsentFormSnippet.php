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
     * @var boolean Add gr2o_mailable to editable fields
     */
    protected $editMailable;

    /**
     *
     * @var array The fields to exhibit
     */
    protected $exhibit = [
        'gr2o_patient_nr',
        'gr2o_id_organization',
        'name',
        'gr2o_email',
        'grs_gender',
        'grs_birthday',
        ];

    /**
     *
     * @var \Gems_Model_RespondentModel
     */
    protected $model;

    /**
     * When true a tabbed form is used.
     *
     * @var boolean
     */
    protected $useTabbedForm = false;

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        if (in_array('name', $this->exhibit)) {
            \Gems_Model_RespondentModel::addNameToModel($this->model, $this->_('Name'));
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
}
