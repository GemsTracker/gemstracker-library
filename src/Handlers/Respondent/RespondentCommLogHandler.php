<?php

namespace Gems\Handlers\Respondent;

use Gems\Exception;
use Gems\Handlers\Setup\CommLogHandler;
use Gems\Legacy\CurrentUserRepository;
use Gems\Model;
use Gems\Model\CommLogModel;
use Gems\Repository\PeriodSelectRepository;
use Gems\Repository\RespondentRepository;
use Gems\Tracker\Respondent;
use Gems\User\Mask\MaskRepository;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

class RespondentCommLogHandler extends CommLogHandler
{
    use GetRespondentTrait;

    protected array $autofilterParameters = ['extraFilter' => 'getRespondentFilter'];

    /**
     * @inheritdoc
     */
    protected array $defaultParameters = [
        'respondent' => 'getRespondent'
    ];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        MaskRepository $maskRepository,
        ProjectOverloader $overloader,
        PeriodSelectRepository $periodSelectRepository,
        CurrentUserRepository $currentUserRepository,
        protected RespondentRepository $respondentRepository,
    ) {
        parent::__construct($responder, $translate, $cache, $maskRepository, $overloader, $periodSelectRepository);

        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

    public function createModel(bool $detailed, string $action): DataReaderInterface
    {
        /**
         * @var CommLogModel $model
         */
        $model = parent::createModel($detailed, $action);
        $model->setKeys([Model::LOG_ITEM_ID => 'grco_id_action']);

        $model->addMap(MetaModelInterface::REQUEST_ID1, 'gr2o_patient_nr');
        $model->addMap(MetaModelInterface::REQUEST_ID2, 'gr2o_id_organization');

        return $model;
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
}
