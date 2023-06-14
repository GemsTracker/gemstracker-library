<?php

namespace Gems\Handlers\Respondent;

use Gems\Exception;
use Gems\Handlers\Setup\CommLogHandler;
use Gems\Model;
use Gems\Repository\PeriodSelectRepository;
use Gems\Repository\RespondentRepository;
use Gems\Tracker\Respondent;
use Gems\User\Mask\MaskRepository;
use MUtil\Model\ModelAbstract;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;
use Zalt\SnippetsLoader\SnippetResponderInterface;

class RespondentCommLogHandler extends CommLogHandler
{
    protected array $autofilterParameters = ['extraFilter' => 'getRespondentFilter'];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        MaskRepository $maskRepository,
        ProjectOverloader $overloader,
        PeriodSelectRepository $periodSelectRepository,
        protected RespondentRepository $respondentRepository,
    ) {
        parent::__construct($responder, $translate, $maskRepository, $overloader, $periodSelectRepository);
    }

    public function createModel(bool $detailed, string $action): ModelAbstract
    {
        /**
         * @var Model\JoinModel $model
         */
        $model = parent::createModel($detailed, $action);
        $model->setKeys([Model::LOG_ITEM_ID => 'grco_id_action']);

        $model->addMap(\MUtil\Model::REQUEST_ID1, 'gr2o_patient_nr');
        $model->addMap(\MUtil\Model::REQUEST_ID2, 'gr2o_id_organization');

        return $model;
    }

    /**
     * Get the respondent object
     *
     * @return Respondent
     */
    public function getRespondent(): Respondent
    {
        static $respondent;

        if (! $respondent) {
            $patientNumber  = $this->request->getAttribute(\MUtil\Model::REQUEST_ID1);
            $organizationId = $this->request->getAttribute(\MUtil\Model::REQUEST_ID2);

            $respondent = $this->respondentRepository->getRespondent($patientNumber, $organizationId);

            if ((! $respondent->exists) && $patientNumber && $organizationId) {
                throw new Exception(sprintf($this->_('Unknown respondent %s.'), $patientNumber));
            }
        }

        return $respondent;
    }

    /**
     * Get filter for current respondent
     *
     * @return array
     */
    public function getRespondentFilter(): array
    {
        return ['grco_id_to' => $this->getRespondentId()];
    }

    /**
     * Retrieve the respondent id
     * (So we don't need to repeat that for every snippet.)
     *
     * @return int
     */
    public function getRespondentId(): int
    {
        return $this->getRespondent()->getId();
    }
}