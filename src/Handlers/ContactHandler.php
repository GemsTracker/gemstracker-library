<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers;

use Gems\Db\ResultFetcher;
use Gems\Locale\Locale;
use Gems\Menu\RouteHelper;
use Gems\Project\ProjectSettings;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 2.0
 */
class ContactHandler extends SnippetLegacyHandlerAbstract
{
    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        protected ResultFetcher $resultFetcher,
    ) {
        parent::__construct($responder, $translate);

        \Gems\Html::init();
    }

    /**
     * The main concact page.
     */
    public function indexAction(): void
    {
        $this->addSnippet('Contact\\IndexSnippet');

        $params = [
            'organizations' => $this->_getOrganizations(),
        ];
        $this->addSnippets('Contact\\OrganizationsListSnippet', $params);
    }

    /**
     * Shows a page with info about this project.
     */
    public function aboutAction(): void
    {
        $this->addSnippet('Contact\\AboutSnippet');

        $params = [
            'organizations' => $this->_getOrganizations(),
        ];
        $this->addSnippet('Contact\\OrganizationsListSnippet', $params);
    }

    /**
     * Shows a page with info about GeMS.
     */
    public function gemsAction(): void
    {
        $this->addSnippet('Contact\\GemsSnippet');
    }

    /**
     * Show screen telling people how to report bugs.
     */
    public function bugsAction(): void
    {
        $this->addSnippet('Contact\\BugsSnippet');
    }

    /**
     * Show the support page.
     */
    public function supportAction(): void
    {
        $params = [
            'organizations' => $this->_getOrganizations(),
        ];
        $this->addSnippet('Contact\\SupportSnippet', $params);
    }

    /**
     * A list of all participating organizations.
     */
    protected function _getOrganizations(): array
    {
        $select = $this->resultFetcher->getSelect('gems__organizations')
            ->columns(['gor_id_organization', 'gor_name', 'gor_url', 'gor_task'])
            ->where('gor_active=1 AND gor_url IS NOT NULL AND gor_task IS NOT NULL')
            ->order('gor_name');

        return $this->resultFetcher->fetchAll($select);
    }
}
