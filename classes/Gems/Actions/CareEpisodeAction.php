<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Expression copyright is undefined on line 42, column 18 in Templates/Scripting/PHPClass.php.
 * @license    No free license, do not copy
 */

namespace Gems\Actions;

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Expression copyright is undefined on line 54, column 18 in Templates/Scripting/PHPClass.php.
 * @license    No free license, do not copy
 * @since      Class available since version 1.8.4 16-May-2018 17:33:39
 */
class CareEpisodeAction extends \Gems\Actions\RespondentChildActionAbstract
{
    /**
     *
     * @var \Gems\Tracker\Respondent
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
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'Agenda\\EpisodeTableSnippet';

    /**
     *
     * @var \Gems\User\User
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
     * The parameters used for the show action
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $showParameters = [
        'bridgeMode' => \MUtil\Model\Bridge\BridgeAbstract::MODE_ROWS,   // Prevent lazyness
        'respondent' => 'getRespondent',
        ];

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showSnippets = ['Generic\\ContentTitleSnippet', 'ModelItemTableSnippetGeneric', 'Agenda\\AppointmentsTableSnippet'];

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil\Model\ModelAbstract
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
                return sprintf($this->_('Episodes of care for respondent number %s'), $patientId);
            }
            return sprintf($this->_('Episodes of care for respondent number %s: %s'), $patientId, $respondent->getName());
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
     * @return \Gems\Tracker\Respondent
     */
    public function getRespondent()
    {
        if (! $this->_respondent) {
            $id = $this->_getParam(\Gems\Model::EPISODE_ID);
            if ($id && ! ($this->_getParam(\MUtil\Model::REQUEST_ID1) || $this->_getParam(\MUtil\Model::REQUEST_ID2))) {
                $episode = $this->loader->getAgenda()->getEpisodeOfCare($id);
                $this->_respondent = $episode->getRespondent();

                if (! $this->_respondent->exists) {
                    throw new \Gems\Exception($this->_('Unknown respondent.'));
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
        $respondent = $this->getRespondent();
        $patientId  = $respondent->getPatientNumber();
        if ($patientId) {
            if ($this->currentUser->areAllFieldsMaskedWhole('grs_first_name', 'grs_surname_prefix', 'grs_last_name')) {
                $for = sprintf($this->_('for respondent number %s'), $patientId);
            } else {
                $for = sprintf($this->_('for respondent number %s'), $patientId);
            }
            $for = ' ' . $for;
        } else {
            $for = '';
        }
        return $this->plural('episode of care', 'episodes of care', $count) . $for;
    }
}
