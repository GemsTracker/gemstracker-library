<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Staff
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Staff;

use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Model\MetaModelLoader;
use Gems\Repository\AccessRepository;
use Gems\Snippets\AutosearchFormSnippet;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Message\StatusMessengerInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Staff
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 28-sep-2015 12:19:23
 */
class StaffSearchSnippet extends AutosearchFormSnippet
{
    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MetaModelLoader $metaModelLoader,
        ResultFetcher $resultFetcher,
        StatusMessengerInterface $messenger,
        CurrentUserRepository $currentUserRepository,
        protected AccessRepository $accessRepository,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $metaModelLoader, $resultFetcher, $messenger);
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

            $optionsA = $this->model->get('gsf_active', 'multiOptions');
            $elementA = $this->_createSelectElement('gsf_active', $optionsA, $this->_('(both)'));
            $elementA->setLabel($this->model->get('gsf_active', 'label'));
            $elements[] = $elementA;

            $optionsT = $this->model->get('has_authenticator_tfa', 'multiOptions');
            $elementT = $this->_createSelectElement('has_authenticator_tfa', $optionsT, $this->_('(all)'));
            $elementT->setLabel($this->model->get('has_authenticator_tfa', 'label'));
            $elements[] = $elementT;
        }

        return $elements;
    }
}
