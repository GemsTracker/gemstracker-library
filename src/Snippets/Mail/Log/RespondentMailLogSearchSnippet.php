<?php

/**
 *
 * @package    Gems
 * @subpackage Pulse
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Mail\Log;

use Gems\Db\DatabaseTranslations;
use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model\MetaModelLoader;
use Gems\Repository\PeriodSelectRepository;
use Gems\Repository\RespondentRepository;
use Gems\Repository\TrackDataRepository;
use Gems\Snippets\AutosearchInRespondentSnippet;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\StatusMessengerInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Pulse
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6
 */
class RespondentMailLogSearchSnippet extends MailLogSearchSnippet
{
    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MenuSnippetHelper $menuSnippetHelper,
        MetaModelLoader $metaModelLoader,
        ResultFetcher $resultFetcher,
        StatusMessengerInterface $messenger,
        PeriodSelectRepository $periodSelectRepository,
        CurrentUserRepository $currentUserRepository,
        TrackDataRepository $trackDataRepository,
        protected readonly RespondentRepository $respondentRepository,
    ) {
        parent::__construct(
            $snippetOptions,
            $requestInfo,
            $translate,
            $menuSnippetHelper,
            $metaModelLoader,
            $resultFetcher,
            $messenger,
            $periodSelectRepository,
            $currentUserRepository,
            $trackDataRepository,
        );
    }

    protected function getOrganizations(): array
    {
        return $this->respondentRepository->getPatientOrganizations(
            $this->requestInfo->getParam(MetaModelInterface::REQUEST_ID1),
            $this->requestInfo->getParam(MetaModelInterface::REQUEST_ID2)
        );
    }

    protected function getTracks(): array
    {
        $select = $this->resultFetcher->getSelect('gems__tracks');
        $select->columns(['gtr_id_track', 'gtr_track_name'])
            ->join('gems__respondent2track', 'gtr_id_track = gr2t_id_track', [])
            ->join('gems__respondent2org', 'gr2t_id_user = gr2o_id_user AND gr2t_id_organization = gr2o_id_organization', [])
            ->where([
                'gr2o_patient_nr' => $this->requestInfo->getParam(MetaModelInterface::REQUEST_ID1),
                'gr2o_id_organization' => $this->requestInfo->getParam(MetaModelInterface::REQUEST_ID2),
            ]);
        return $this->resultFetcher->fetchPairs($select);
    }
}
