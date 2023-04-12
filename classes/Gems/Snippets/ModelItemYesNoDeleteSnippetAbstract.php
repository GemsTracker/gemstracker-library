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

use Gems\Audit\AuditLog;
use Gems\MenuNew\MenuSnippetHelper;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Html\Html;
use Zalt\Message\MessengerInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelBridge\DetailTableBridge;
use Zalt\Snippets\ModelYesNoDeleteSnippetAbstract;
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
abstract class ModelItemYesNoDeleteSnippetAbstract extends ModelYesNoDeleteSnippetAbstract
{
    /**
     *
     * @var AuditLog
     */
    // protected $accesslog;

    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'displayer';

    protected array $cacheTags = [];

    /**
     * Optional title to display at the head of this page.
     *
     * @var string Optional
     */
    protected $displayTitle;

    public function __construct(SnippetOptions $snippetOptions,
                                protected RequestInfo $requestInfo,
                                protected MenuSnippetHelper $menuHelper,
                                TranslatorInterface $translate,
                                protected MessengerInterface $messenger,
                                protected CacheItemPoolInterface $cache,
    )
    {
        parent::__construct($snippetOptions, $this->requestInfo, $translate);
    }

    public function getHtmlOutput()
    {
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

    public function hasHtmlOutput() : bool
    {
        if (! $this->abortUrl) {
            $this->abortUrl = $this->menuHelper->getCurrentParentUrl();
        }
        if (! $this->afterDeleteUrl) {
            $this->afterDeleteUrl = $this->menuHelper->getRouteUrl($this->menuHelper->getRelatedRoute('index'), $this->requestInfo->getParams()) ?: '';
        }

        return parent::hasHtmlOutput();
    }
    protected function performAction()
    {
        // $data = $this->getModel()->loadFirst();

        parent::performAction();

        if ($this->cacheTags && ($this->cache instanceof TagAwareCacheInterface)) {
            $this->cache->invalidateTags($this->cacheTags);
        }

        // $this->accesslog->logChange($this->request, $this->getTitle(), $data);
    }

    /**
     * Set the footer of the browse table.
     *
     * Overrule this function to set the header differently, without
     * having to recode the core table building code.
     *
     * @param \Zalt\Snippets\ModelBridge\DetailTableBridge $bridge
     * @param \Zalt\Model\Data\DataReaderInterface $dataModel
     * @return void
     */
    protected function setShowTableFooter(DetailTableBridge $bridge, DataReaderInterface $dataModel)
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
