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

use Gems\Config\ConfigAccessor;
use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model\MetaModelLoader;
use Gems\Repository\PeriodSelectRepository;
use Gems\Repository\TrackDataRepository;
use Gems\Snippets\AutosearchInRespondentSnippet;
use Gems\User\User;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\StatusMessengerInterface;
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
class MailLogSearchSnippet extends AutosearchInRespondentSnippet
{

    protected User $currentUser;
    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        ConfigAccessor $configAccessor,
        MenuSnippetHelper $menuSnippetHelper,
        MetaModelLoader $metaModelLoader,
        ResultFetcher $resultFetcher,
        StatusMessengerInterface $messenger,
        PeriodSelectRepository $periodSelectRepository,
        CurrentUserRepository $currentUserRepository,
        protected TrackDataRepository $trackDataRepository,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $configAccessor, $menuSnippetHelper, $metaModelLoader, $resultFetcher, $messenger, $periodSelectRepository);
        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

    /**
     * Returns a text element for autosearch. Can be overruled.
     *
     * The form / html elements to search on. Elements can be grouped by inserting null's between them.
     * That creates a distinct group of elements
     *
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array Of \Zend_Form_Element's or static tekst to add to the html or null for group breaks.
     */
    protected function getAutoSearchElements(array $data)
    {
        // Search text
        $elements = parent::getAutoSearchElements($data);

        $this->addPeriodSelectors($elements, [
            'grco_created' => $this->_('Date sent'),
        ]);

        $elements[] = null;

        $tracks = $this->getTracks();
        $surveys = $this->getSurveys($tracks);
        $organizations = $this->getOrganizations();

        $elements[] = $this->_createSelectElement(
                'gto_id_track',
                $tracks,
                $this->_('(select a track)')
                );

        $elements[] = $this->_createSelectElement(
                'gto_id_survey',
                $surveys,
                $this->_('(all surveys)')
                );

        $elements[] = $this->_createSelectElement(
                'grco_organization',
                $organizations,
                $this->_('(all organizations)')
                );

        return $elements;
    }

    protected function getOrganizations(): array
    {
        return $this->currentUser->getRespondentOrganizations();
    }

    protected function getSurveys(array $tracks): array
    {
        $select = $this->resultFetcher->getSelect('gems__surveys');
        $select->columns(['gsu_id_survey', 'gsu_survey_name'])
            ->join('gems__rounds', 'gsu_id_survey = gro_id_survey', [])
            ->where(['gro_id_track' => array_keys($tracks)])
            ->group('gsu_id_survey')
            ->order('gsu_survey_name');
        return $this->resultFetcher->fetchPairs($select);
    }

    protected function getTracks(): array
    {
        return $this->trackDataRepository->getTracksForOrgs($this->currentUser->getRespondentOrganizations());
    }
}
;