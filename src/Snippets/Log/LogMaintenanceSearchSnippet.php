<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets_Log
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Log;

use Gems\Db\ResultFetcher;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model\MetaModelLoader;
use Gems\Snippets\AutosearchFormSnippet;
use Gems\Util\Translated;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\StatusMessengerInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets_Log
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 16-feb-2015 19:46:34
 */
class LogMaintenanceSearchSnippet extends AutosearchFormSnippet
{

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MenuSnippetHelper $menuSnippetHelper,
        MetaModelLoader $metaModelLoader,
        ResultFetcher $resultFetcher,
        StatusMessengerInterface $messenger,
        protected Translated $translatedUtil,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $menuSnippetHelper, $metaModelLoader, $resultFetcher, $messenger);
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
        $yesNo = $this->translatedUtil->getYesNo();
        $elements = parent::getAutoSearchElements($data);

        $elements[] = $this->_createSelectElement('gls_when_no_user', $yesNo, $this->_('(any when no user)'));
        $elements[] = $this->_createSelectElement('gls_on_action',    $yesNo, $this->_('(any on action)'));
        $elements[] = $this->_createSelectElement('gls_on_post',      $yesNo, $this->_('(any on post)'));
        $elements[] = $this->_createSelectElement('gls_on_change',    $yesNo, $this->_('(any on change)'));

        return $elements;
    }
}