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
        'gr2o_id_organization',
        'gr2o_patient_nr',
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
}
