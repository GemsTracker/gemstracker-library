<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets;

use Gems\MenuNew\MenuSnippetHelper;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Html\Html;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelBridge\DetailTableBridge;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * Ask Yes/No conformation for deletion and deletes item when confirmed.
 *
 * Can be used for other uses than delete by overriding performAction().
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4.4
 */
abstract class ModelItemYesNoDeleteSnippetAbstract extends \Zalt\Snippets\ModelYesNoDeleteSnippetAbstract
{
    /**
     *
     * @var \Gems\AccessLog
     */
    // protected $accesslog;

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

    public function __construct(SnippetOptions $snippetOptions,
                                protected RequestInfo $requestInfo,
                                protected MenuSnippetHelper $menuHelper,
                                TranslatorInterface $translate)
    {
        parent::__construct($snippetOptions, $this->requestInfo, $translate);
    }

    public function getHtmlOutput()
    {
        if (! $this->abortUrl) {
            $this->abortUrl = $this->menuHelper->getCurrentParentRoute();
        }
        if (! $this->afterDeleteUrl) {
            $this->afterDeleteUrl = $this->menuHelper->getRouteUrl($this->menuHelper->getRelatedRoute('index'), $this->requestInfo->getParams()) ?: '';
        }
        
        if ($table = parent::getHtmlOutput()) {
            if ($title = $this->getTitle()) {
                $htmlDiv = Html::div();

                $htmlDiv->h3($title);

                $this->applyHtmlAttributes($table);

                $htmlDiv[] = $table;

                return $htmlDiv;
            } else {
                return $table;
            }
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
     * /
    protected function performAction()
    {
        // $data = $this->getModel()->loadFirst();

        parent::performAction();

        // $this->accesslog->logChange($this->request, $this->getTitle(), $data);
    }

    /**
     * Set the footer of the browse table.
     *
     * Overrule this function to set the header differently, without
     * having to recode the core table building code.
     *
     * @param \Zalt\Snippets\ModelBridge\DetailTableBridge $bridge
     * @param \Zalt\Model\Data\DataReaderInterface $model
     * @return void
     */
    protected function setShowTableFooter(DetailTableBridge $bridge, DataReaderInterface $model)
    {
        $footer = $bridge->tfrow();

        $startUrl = $this->requestInfo->getBasePath();

        $footer[] = $this->getQuestion();
        $footer[] = ' ';
        $footer->actionLink([$startUrl, $this->confirmParameter => 1], $this->_('Yes'));
        if ($this->abortUrl) {
            $footer[] = ' ';
            $footer->actionLink([$this->abortUrl], $this->_('No'));
        }
    }
}
