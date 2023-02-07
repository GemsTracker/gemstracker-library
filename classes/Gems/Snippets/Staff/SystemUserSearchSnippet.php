<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Staff
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Staff;

use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Repository\AccessRepository;
use Gems\Snippets\AutosearchFormSnippet;
use Gems\User\User;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Staff
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 02-Sep-2019 17:53:47
 */
class SystemUserSearchSnippet extends AutosearchFormSnippet
{
    protected User $currentUser;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        ResultFetcher $resultFetcher,
        protected AccessRepository $accessRepository,
        CurrentUserRepository $currentUserRepository,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $resultFetcher);
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
        $elements = parent::getAutoSearchElements($data);

        if ($elements) {
            $optionsG = $this->accessRepository->getGroups();
            $elementG = $this->_createSelectElement('gsf_id_primary_group', $optionsG, $this->_('(all groups)'));
            $elements[] = $elementG;

            $optionsO = $this->currentUser->getAllowedOrganizations();
            if (count($optionsO) > 1) {
                $elementO = $this->_createSelectElement('gsf_id_organization', $optionsO, $this->_('(all organizations)'));
                $elements[] = $elementO;
            }

            $optionsT = [
                'gsf_is_embedded'      => $this->_('Is embedder'),
                'gsf_logout_on_survey' => $this->_('Is Logout on survey'),
                ];
            $elementT = $this->_createSelectElement('specials', $optionsT, $this->_('(all)'));
            $elements[] = $elementT;

            $optionsA = $this->model->get('gsf_active', 'multiOptions');
            $elementA = $this->_createSelectElement('gsf_active', $optionsA, $this->_('(both)'));
            $elementA->setLabel($this->model->get('gsf_active', 'label'));
            $elements[] = $elementA;
        }

        return $elements;
    }
}
