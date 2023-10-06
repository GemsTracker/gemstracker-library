<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Expression copyright is undefined on line 42, column 18 in Templates/Scripting/PHPClass.php.
 * @license    No free license, do not copy
 */

namespace Gems\Handlers\Respondent;

use Gems\Agenda\Agenda;
use Gems\Legacy\CurrentUserRepository;
use Gems\Model;
use Gems\Model\EpisodeOfCareModel;
use Gems\Repository\RespondentRepository;
use Gems\Snippets\Agenda\AppointmentsTableSnippet;
use Gems\Snippets\Agenda\EpisodeTableSnippet;
use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\Generic\CurrentButtonRowSnippet;
use Gems\Snippets\ModelDetailTableSnippet;
use Gems\Tracker\Respondent;
use Gems\User\Mask\MaskRepository;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Bridge\BridgeInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Expression copyright is undefined on line 54, column 18 in Templates/Scripting/PHPClass.php.
 * @license    No free license, do not copy
 * @since      Class available since version 1.8.4 16-May-2018 17:33:39
 */
class CareEpisodeHandler extends RespondentChildHandlerAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $autofilterParameters = [
        'extraFilter' => 'getPatientFilter',
        'extraSort'   => ['gec_admission_time' => SORT_DESC],
    ];

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $autofilterSnippets = [
        EpisodeTableSnippet::class,
        ];

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
    protected array $indexParameters = [
        'contentTitle' => 'getContentTitle',
    ];

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
    protected array $showParameters = [
        // 'bridgeMode' => BridgeInterface::MODE_ROWS,   // Prevent lazyness
        'respondent' => 'getRespondent',
        ];

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected array $showSnippets = [
        ContentTitleSnippet::class,
        ModelDetailTableSnippet::class,
        CurrentButtonRowSnippet::class,
        AppointmentsTableSnippet::class,
        ];

    public function __construct(
        SnippetResponderInterface $responder, 
        TranslatorInterface $translate, 
        RespondentRepository $respondentRepository, 
        CurrentUserRepository $currentUserRepository,
        protected Agenda $agenda,
        protected MaskRepository $maskRepository,
        protected Model $modelLoader,
    )
    {
        parent::__construct($responder, $translate, $respondentRepository, $currentUserRepository);
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
     */
    protected function createModel(bool $detailed, string $action): EpisodeOfCareModel
    {
        $respondent = $this->getRespondent();

        $model = $this->modelLoader->createEpisodeOfCareModel();

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
            if ($this->maskRepository->areAllFieldsMaskedWhole('grs_first_name', 'grs_surname_prefix', 'grs_last_name')) {
                return sprintf($this->_('Episodes of care for respondent number %s'), $patientId);
            }
            return sprintf($this->_('Episodes of care for respondent number %s: %s'), $patientId, $respondent->getName());
        }
        return $this->getIndexTitle();
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return string
     */
    public function getIndexTitle(): string
    {
        return $this->_('Episodes of care');
    }

    public function getPatientFilter()
    {
        $params = $this->requestInfo->getRequestMatchedParams();
        return [
            'gr2o_patient_nr' => $params[\MUtil\Model::REQUEST_ID1],
            'gr2o_id_organization' => $params[\MUtil\Model::REQUEST_ID2],
        ];
    }

    /**
     * Get the respondent object
     *
     * @return Respondent
     */
    public function getRespondent(): Respondent
    {
        if (! $this->_respondent) {
            $id = $this->requestInfo->getParam(Model::EPISODE_ID);
            $patientNr = $this->requestInfo->getParam(\MUtil\Model::REQUEST_ID1);
            $orgId = $this->requestInfo->getParam(\MUtil\Model::REQUEST_ID2);
            if ($id && ! ($patientNr || $orgId)) {
                $episode = $this->agenda->getEpisodeOfCare($id);
                $this->_respondent = $episode->getRespondent();

                if (! $this->_respondent->exists) {
                    throw new \Gems\Exception($this->_('Unknown respondent.'));
                }

                // $this->_respondent->applyToMenuSource($this->menu->getParameterSource());
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
     * @return string
     */
    public function getTopic(int $count = 1): string
    {
        $respondent = $this->getRespondent();
        $patientId  = $respondent->getPatientNumber();
        if ($patientId) {
            if (false && $this->maskRepository->areAllFieldsMaskedWhole('grs_first_name', 'grs_surname_prefix', 'grs_last_name')) {
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
