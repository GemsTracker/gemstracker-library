<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Agenda;

use Gems\Agenda\Agenda;
use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model\MetaModelLoader;
use Gems\Repository\PeriodSelectRepository;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Gems\Snippets\AutosearchPeriodFormSnippet;
use Zalt\Message\StatusMessengerInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class CalendarSearchSnippet extends AutosearchPeriodFormSnippet
{
    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MenuSnippetHelper $menuSnippetHelper,
        MetaModelLoader $metaModelLoader,
        ResultFetcher $resultFetcher,
        StatusMessengerInterface $messenger,
        PeriodSelectRepository $periodSelectRepository,
        protected Agenda $agenda,
        CurrentUserRepository $currentUserRepository,
    )
    {
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
    public function getAutoSearchElements(array $data)
    {
        $elements = parent::getAutoSearchElements($data);

        $orgs = $this->currentUser->getRespondentOrganizations();
        if (count($orgs) > 1) {
            $elements[] = $this->_createSelectElement('gap_id_organization', $orgs, $this->_('(all organizations)'));
        }

        $locations = $this->agenda->getLocations();
        if (count($locations) > 1) {
            $elements[] = $this->_createSelectElement('gap_id_location', $locations, $this->_('(all locations)'));
        }

        $elements[] = null;

        $this->addPeriodSelectors($elements, 'gap_admission_time');

        return $elements;
    }
}
