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
 */

class RespondentController extends Gems_Default_RespondentAction
{
    public $showSnippets = array(
        'RespondentDetailsSnippet',
    	'AddTracksSnippet',
        'RespondentTokenTabsSnippet',
        'RespondentTokenSnippet',
    );

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_FormBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @param array $data The data that will later be loaded into the form
     * @param optional boolean $new Form should be for a new element
     * @return void|array When an array of new values is return, these are used to update the $data array in the calling function
     */
    protected function addFormElements(MUtil_Model_FormBridge $bridge, MUtil_Model_ModelAbstract $model, array $data, $new = false)
    {       
        if (APPLICATION_ENV !== 'production') {
            $bsn = new MUtil_Validate_Dutch_Burgerservicenummer();
            $num = mt_rand(100000000, 999999999);

            while (! $bsn->isValid($num)) {
                $num++;
            }

            $model->set('grs_bsn', 'description', 'Willekeurig voorbeeld BSN: ' . $num);

        } else {
            $model->set('grs_bsn', 'description', $this->_('Enter a 9-digit BSN number.'));
        }

        $ucfirst = new Zend_Filter_Callback('ucfirst');

        $bridge->addHidden(  'grs_id_user');
        $bridge->addHidden(  'gr2o_id_organization');
        $bridge->addHidden(   $model->getKeyCopyName('gr2o_patient_nr'));

        $bridge->addTab(    'caption1')->h4($this->_('Identification'));
        $bridge->addText(    'grs_bsn',            'label', $this->_('BSN'), 'size', 10, 'maxlength', 12)
            ->addValidator(  new MUtil_Validate_Dutch_Burgerservicenummer())
            ->addValidator(  $model->createUniqueValidator('grs_bsn'))
            ->addFilter(     'Digits');
        $bridge->addText(    'gr2o_patient_nr',    'label', $this->_('Patient number'), 'size', 15, 'minlength', 4)
            ->addValidator(  $model->createUniqueValidator(array('gr2o_patient_nr', 'gr2o_id_organization'), array('gr2o_id_user' => 'grs_id_user', 'gr2o_id_organization')));

        $bridge->addText(    'grs_first_name')
            ->addFilter(     $ucfirst);
        $bridge->addText(    'grs_surname_prefix', 'description', 'de, van der, \'t, etc...');
        $bridge->addText(    'grs_last_name',      'required', true)
            ->addFilter(     $ucfirst);

        $bridge->addTab(    'caption2')->h4($this->_('Medical data'));
        $bridge->addRadio(   'grs_gender',         'separator', '', 'multiOptions', $this->util->getTranslated()->getGenders());
        $year = intval(date('Y')); // Als jQuery 1.4 gebruikt wordt: yearRange = c-130:c0
        $bridge->addDate(    'grs_birthday',       'jQueryParams', array('defaultDate' => '-30y', 'maxDate' => 0, 'yearRange' => ($year - 130) . ':' . $year))
            ->addValidator(new MUtil_Validate_Date_DateBefore());

        $bridge->addSelect(  'gr2o_id_physician');
        $bridge->addText(    'gr2o_treatment',     'size', 30, 'description', $this->_('DBC\'s, etc...'));
        $bridge->addTextarea('gr2o_comments',      'rows', 4, 'cols', 60);

        $bridge->addTab(    'caption3')->h4($this->_('Contact information'));
        // Setting e-mail to required is niet mogelijk, grijpt te diep in
        // misschien later proberen met ->addGroup('required', 'true'); ???
        $bridge->addText(    'grs_email',          'size', 30) // , 'required', true, 'AutoInsertNotEmptyValidator', false)
            ->addValidator(  'SimpleEmail');
        $bridge->addCheckBox('calc_email',         'label', $this->_('Respondent has no e-mail'));
        $bridge->addText(    'grs_address_1',      'size',  40, 'description', $this->_('With housenumber'))
            ->addFilter(     $ucfirst);
        $bridge->addText(    'grs_address_2',      'size', 40);
        $bridge->addText(    'grs_zipcode',        'size', 7, 'description', '0000 AA');
        $bridge->addFilter(  'grs_zipcode',        new Gems_Filter_DutchZipcode());
        $bridge->addText(    'grs_city')
            ->addFilter(     $ucfirst);
        $bridge->addSelect(  'grs_iso_country',    'label', $this->_('Country'), 'multiOptions', $this->util->getLocalized()->getCountries());
        $bridge->addText(    'grs_phone_1',        'size', 15)
            ->addValidator(  'Phone');

        $bridge->addTab(    'caption4')->h4($this->_('Settings'));
        $bridge->addSelect(  'grs_iso_lang',       'label', $this->_('Language'), 'multiOptions', $this->util->getLocalized()->getLanguages());
        $bridge->addRadio(   'gr2o_consent',       'separator', '', 'description',  $this->_('Has the respondent signed the informed consent letter?'));
    }

    public function afterSave(array $data, $isNew)
    {
        Gems_AccessLog::getLog($this->db)->logSaveRespondent($data['grs_id_user'], $this->getRequest());
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
        $model = parent::createModel($detailed, $action);

        if ($detailed) {
        	$model->set('gr2o_comments',     'label', $this->_('Comments'));
        	$model->set('gr2o_id_physician', 'label', $this->_('Physician'), 'multiOptions', MUtil_Lazy::call(array($this, 'getPhysicians')));
        	$model->set('gr2o_treatment',    'label', $this->_('Treatment'));

            $model->addColumn('CASE WHEN grs_email IS NULL OR LENGTH(TRIM(grs_email)) = 0 THEN 1 ELSE 0 END', 'calc_email');
        }

        $model->set('gr2o_id_organization', 'default', $model->getCurrentOrganization());

        return $model;
    }

    public function getPhysicians()
    {
        $session = new Zend_Session_Namespace('Pulse_' . __FILE__);

        if (! isset($session->physicians)) {
            $organizationId = $this->escort->getCurrentOrganization();

            $values = $this->db->fetchPairs("
                SELECT gsf_id_user,
                    CONCAT(gsf_last_name, ', ', COALESCE(CONCAT(gsf_first_name, ' '), ''), COALESCE(gsf_surname_prefix, '')) AS name
                    FROM gems__staff INNER JOIN gems__groups ON gsf_id_primary_group = ggp_id_group
                    WHERE gsf_active=1 AND gsf_id_organization = ? AND ggp_role = 'physician'
                    ORDER BY 2", $organizationId);

            $session->physicians = $values;
        }

        return $this->util->getTranslated()->getEmptyDropdownArray() + $session->physicians;
    }

    protected function openedRespondent($patientId, $orgId = null, $userId = null)
    {
        Gems_AccessLog::getLog($this->db)->logShowRespondent($userId, $this->getRequest());

        return parent::openedRespondent($patientId, $orgId, $userId);
    }
}
