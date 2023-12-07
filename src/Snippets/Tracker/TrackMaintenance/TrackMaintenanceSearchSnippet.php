<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker\TrackMaintenance;

use Gems\Db\ResultFetcher;
use Gems\Html;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model\MetaModelLoader;
use Gems\User\User;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\StatusMessengerInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 9-sep-2015 18:10:55
 */
class TrackMaintenanceSearchSnippet extends \Gems\Snippets\AutosearchFormSnippet
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
        CurrentUserRepository $currentUserRepository,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $menuSnippetHelper, $metaModelLoader, $resultFetcher, $messenger);
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
            $br = Html::create('br');
            $elements[] = $this->_createSelectElement('gtr_track_class', $this->model->getMetaModel(), $this->_('(all track engines)'));

            $elements[] = $br;

            $optionsA = [
                1 => $this->_('Yes'),
                0 => $this->_('No'),
                2 => $this->_('Expired'),
                3 => $this->_('Future')
            ];
            $elementA = $this->_createSelectElement('active', $optionsA, $this->_('(all)'));
            $elementA->setLabel($this->model->getMetaModel()->get('gtr_active', 'label'));
            $elements[] = $elementA;

            $optionsO = $this->currentUser->getRespondentOrganizations();
            $elementO = $this->_createSelectElement('org', $optionsO, $this->_('(all organizations)'));
            $elements[] = $elementO;
        }

        return $elements;
    }
}
