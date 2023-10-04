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

use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model\MetaModelLoader;
use Gems\Repository\PeriodSelectRepository;
use Gems\Repository\TrackDataRepository;
use Gems\Snippets\AutosearchInRespondentSnippet;
use Gems\User\User;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
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
        MenuSnippetHelper $menuSnippetHelper,
        MetaModelLoader $metaModelLoader,
        ResultFetcher $resultFetcher,
        StatusMessengerInterface $messenger,
        PeriodSelectRepository $periodSelectRepository,
        CurrentUserRepository $currentUserRepository,
        protected TrackDataRepository $trackDataRepository,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $menuSnippetHelper, $metaModelLoader, $resultFetcher, $messenger, $periodSelectRepository);
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

        $this->_addPeriodSelectors($elements, [
            'grco_created' => $this->_('Date sent'),
        ]);

        $elements[] = null;

        $elements[] = $this->_createSelectElement(
                'gto_id_track',
                $this->trackDataRepository->getAllTracks(),
                $this->_('(select a track)')
                );

        $elements[] = $this->_createSelectElement(
                'gto_id_survey',
                $this->trackDataRepository->getAllSurveys(),
                $this->_('(all surveys)')
                );

        $elements[] = $this->_createSelectElement(
                'grco_organization',
                $this->currentUser->getRespondentOrganizations(),
                $this->_('(all organizations)')
                );

        return $elements;
    }
}
