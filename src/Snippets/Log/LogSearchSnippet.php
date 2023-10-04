<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Log
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Log;

use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model\MetaModelLoader;
use Gems\Repository\PeriodSelectRepository;
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
 * @subpackage Snippets\Log
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 16-feb-2015 19:46:34
 */
class LogSearchSnippet extends AutosearchInRespondentSnippet
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
        protected CurrentUserRepository $currentUserRepository,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $menuSnippetHelper, $metaModelLoader, $resultFetcher, $messenger, $periodSelectRepository);
        $this->currentUser = $this->currentUserRepository->getCurrentUser();
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

        $this->addPeriodSelectors($elements, 'gla_created');

        $elements[] = null;

        $elements[] = $this->_('Specific action');

        $sql = "SELECT gls_id_action, gls_name
                    FROM gems__log_setup
                    WHERE gls_when_no_user = 1 OR gls_on_action = 1 OR gls_on_change = 1 OR gls_on_post = 1
                    ORDER BY gls_name";

        $elements[] = $this->_createSelectElement('gla_action', $sql, $this->_('(any action)'));

        $elements[] = $this->_createSelectElement(
                'gla_organization',
                $this->currentUser->getAllowedOrganizations(),
                $this->_('(all organizations)')
                );

        return $elements;
    }
}
