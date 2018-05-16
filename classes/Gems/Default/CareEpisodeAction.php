<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Expression copyright is undefined on line 42, column 18 in Templates/Scripting/PHPClass.php.
 * @license    No free license, do not copy
 */

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Expression copyright is undefined on line 54, column 18 in Templates/Scripting/PHPClass.php.
 * @license    No free license, do not copy
 * @since      Class available since version 1.8.4 16-May-2018 17:33:39
 */
class Gems_Default_CareEpisodeAction extends \Gems_Default_RespondentChildActionAbstract
{
    /**
     *
     * @var \Gems_Tracker_Respondent
     */
    private $_respondent;

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterParameters = array(
        'extraSort'   => array('gec_admission_time' => SORT_DESC),
        );

    /**
     *
     * @var \Gems_User_User
     */
    public $currentUser;

    /**
     * The parameters used for the index action minus those in autofilter.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $indexParameters = array(
        'contentTitle' => 'getContentTitle',
        );

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel($detailed, $action)
    {
        $respondent = $this->getRespondent();

        $model = $this->loader->getModels()->createEpisodeOfCareModel();

        if ($detailed) {
            if (('edit' === $action) || ('create' === $action)) {
                $model->applyEditSettings($respondent->getOrganizationId(), $respondent->getId());

                // When there is something saved, then set manual edit to 1
                $model->setSaveOnChange('gec_manual_edit');
                $model->setOnSave(      'gec_manual_edit', 1);
            } else {
                $model->applyDetailSettings();
            }
        } else {
            $model->applyBrowseSettings();
            $model->addFilter(array(
                'gec_id_user'         => $respondent->getId(),
                'gec_id_organization' => $respondent->getOrganizationId(),
                ));
        }

        return $model;
    }

    /**
     * Helper function to get the informed title for the index action.
     *
     * @return $string
     */
    public function getContentTitle()
    {
        $respondent = $this->getRespondent();
        $patientId  = $respondent->getPatientNumber();
        if ($patientId) {
            if ($this->currentUser->areAllFieldsMaskedWhole('grs_first_name', 'grs_surname_prefix', 'grs_last_name')) {
                return sprintf($this->_('Episoded of care for respondent number %s'), $patientId);
            }
            return sprintf($this->_('Episoded of care for respondent number %s: %s'), $patientId, $respondent->getName());
        }
        return $this->getIndexTitle();
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Episodes of care');
    }

    /**
     * Get the respondent object
     *
     * @return \Gems_Tracker_Respondent
     */
    protected function getRespondent()
    {
        if (! $this->_respondent) {
            $id = $this->_getParam(\Gems_Model::EPISODE_ID);
            if ($id && ! ($this->_getParam(\MUtil_Model::REQUEST_ID1) || $this->_getParam(\MUtil_Model::REQUEST_ID2))) {
                $episode = $this->loader->getAgenda()->getEpisodeOfCare($id);
                $this->_respondent = $episode->getRespondent();

                if (! $this->_respondent->exists) {
                    throw new \Gems_Exception($this->_('Unknown respondent.'));
                }

                $this->_respondent->applyToMenuSource($this->menu->getParameterSource());
            } else {
                $this->_respondent = parent::getRespondent();
            }
        }

        return $this->_respondent;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('episode of care', 'episodes of care', $count);
    }
}
