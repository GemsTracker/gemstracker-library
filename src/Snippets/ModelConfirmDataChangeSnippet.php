<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets;

use Gems\Audit\AuditLog;
use Gems\Menu\MenuSnippetHelper;
use Mezzio\Helper\UrlHelper;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\Html;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\Snippets\ModelBridge\DetailTableBridge;
use Zalt\Snippets\ModelConfirmDataChangeSnippetAbstract;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 30-sep-2015 19:01:28
 */
class ModelConfirmDataChangeSnippet extends ModelConfirmDataChangeSnippetAbstract
{
    /**
     *
     * @var AuditLog
     */
    protected $auditlog;

    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'displayer';

    /**
     * Optional title to display at the head of this page.
     *
     * @var string Optional
     */
    protected $displayTitle;

    /**
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $model;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        protected MenuSnippetHelper $menuSnippetHelper,
        protected UrlHelper $urlHelper,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate);
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel(): FullDataInterface
    {
        return $this->model;
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput()
    {
        $table = parent::getHtmlOutput();
        $title = $this->getTitle();

        if ($title) {
            $htmlDiv = Html::div();

            $htmlDiv->h3($title);

            $this->applyHtmlAttributes($table);

            $htmlDiv[] = $table;

            return $htmlDiv;
        } else {
            return $table;
        }
    }

    /**
     * An optional title for the head of the page.
     *
     * @return string
     */
    protected function getTitle()
    {
        return $this->displayTitle;
    }

    /**
     * Overrule this function if you want to perform a different
     * action than deleting when the user choose 'yes'.
     */
    protected function performAction()
    {
        parent::performAction();

        /*$this->accesslog->logChange(
                $this->request,
                $this->getTitle(),
                $this->saveData + $this->getModel()->loadFirst()
                );
        */
    }

    /**
     * Set the footer of the browse table.
     *
     * Overrule this function to set the header differently, without
     * having to recode the core table building code.
     *
     * @param DetailTableBridge $bridge
     * @param DataReaderInterface $dataModel
     * @return void
     */
    protected function setShowTableFooter(DetailTableBridge $bridge, DataReaderInterface $dataModel)
    {
        $footer = $bridge->tfrow();

        $footer[] = $this->getQuestion();
        $footer[] = ' ';

        $currentRoute = $this->menuSnippetHelper->getCurrentRoute();
        $currentRouteParameters = $this->requestInfo->getRequestMatchedParams();
        $currentRouteParameters[$this->confirmParameter] = 1;

        $confirmUrl = $this->urlHelper->generate($currentRoute, $currentRouteParameters, [$this->confirmParameter => 1]);

        $footer->actionLink($confirmUrl, $this->_('Yes'));
        $footer[] = ' ';

        $cancelUrl = $this->menuSnippetHelper->getRelatedRouteUrl($this->abortAction);

        $footer->actionLink($cancelUrl, $this->_('No'));
    }
}
