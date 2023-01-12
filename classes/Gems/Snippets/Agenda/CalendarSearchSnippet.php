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
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
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
class CalendarSearchSnippet extends AutosearchFormSnippet
{
    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    public function __construct(
        SnippetOptions $snippetOptions,
        protected RequestInfo $requestInfo,
        TranslatorInterface $translate,
        protected ResultFetcher $resultFetcher,
        protected Agenda $agenda,
        CurrentUserRepository $currentUserRepository,
    )
    {
        parent::__construct($snippetOptions, $this->requestInfo, $translate, $this->resultFetcher);
        
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

        $this->_addPeriodSelectors($elements, 'gap_admission_time');

        return $elements;
    }
}
