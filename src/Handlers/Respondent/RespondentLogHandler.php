<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Respondent;

use Gems\Exception;
use Gems\Handlers\LogHandler;
use Gems\Legacy\CurrentUserRepository;
use Gems\Model;
use Gems\Model\LogModel;
use Gems\Repository\PeriodSelectRepository;
use Gems\Repository\RespondentRepository;
use Gems\Tracker\Respondent;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 16-apr-2015 17:36:20
 */
class RespondentLogHandler extends LogHandler
{
    use GetRespondentTrait;

    /**
     * @inheritdoc
     */
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
        PeriodSelectRepository $periodSelectRepository,
        LogModel $logModel,
        protected readonly CurrentUserRepository $currentUserRepository,
        protected RespondentRepository $respondentRepository,
    ) {
        parent::__construct($responder, $translate, $cache, $periodSelectRepository, $logModel);

        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

    protected function createModel(bool $detailed, string $action): FullDataInterface
    {
        parent::createModel($detailed, $action);

        $this->logModel->addTable('gems__respondent2org', ['gla_respondent_id' => 'gr2o_id_user', 'gla_organization' => 'gr2o_id_organization']);
        $this->logModel->getMetaModel()->setKeys([Model::LOG_ITEM_ID => 'gla_id']);

        $this->logModel->getMetaModel()->addMap(Model::REQUEST_ID1, 'gr2o_patient_nr');
        $this->logModel->getMetaModel()->addMap(Model::REQUEST_ID2, 'gr2o_id_organization');

        return $this->logModel;
    }

    /**
     * Get filter for current respondent
     *
     * @return array
     */
    public function getRespondentFilter(): array
    {
        return ['gla_respondent_id' => $this->getRespondentId()];
    }
}
