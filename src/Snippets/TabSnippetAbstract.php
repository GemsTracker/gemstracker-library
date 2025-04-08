<?php

namespace Gems\Snippets;

use Gems\Menu\MenuSnippetHelper;
use Zalt\Base\RequestInfo;
use Zalt\Html\Html;
use Zalt\Html\HtmlInterface;
use Zalt\Snippets\SnippetAbstract;
use Zalt\SnippetsLoader\SnippetOptions;

abstract class TabSnippetAbstract extends SnippetAbstract
{
    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'tabrow nav nav-tabs';

    /**
     *
     * @var ?string Id of the current tab
     */
    protected ?string $currentTab = null;

    /**
     *
     * @var ?string Id of default tab
     */
    protected ?string $defaultTab = null;

    /**
     * Show bar when there is only a single tab
     *
     * @var boolean
     */
    protected bool $displaySingleTab = false;

    /**
     *
     * @var string Class attribute for active tab
     */
    protected string $tabActiveClass = 'active';

    protected string $tabLinkClass = 'nav-link';

    /**
     *
     * @var string Class attribute for all tabs
     */
    protected string $tabClass = 'tab nav-item';

    public function __construct(SnippetOptions $snippetOptions, RequestInfo $requestInfo, protected MenuSnippetHelper $menuSnippetHelper)
    {
        parent::__construct($snippetOptions, $requestInfo);
    }

    /**
     * Sets the default and current tab and returns the current
     *
     * @return ?string The current tab
     */
    public function getCurrentTab(): ?string
    {
        $tabs = $this->getTabs();

        // When empty, first is default
        if (null === $this->defaultTab) {
            reset($tabs);
            $this->defaultTab = key($tabs);
        }
        if (null === $this->currentTab) {
            $this->currentTab = null;
            $queryParams = $this->requestInfo->getRequestQueryParams();
            if (isset($queryParams[$this->getParameterKey()])) {
                $this->currentTab = $queryParams[$this->getParameterKey()];
            }
        }

        // Param can exist and be empty or can have a false value
        if (! ($this->currentTab && isset($tabs[$this->currentTab])))  {
            $this->currentTab = $this->defaultTab;
        }

        return $this->currentTab;
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @return HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(): ?HtmlInterface
    {
        $tabs = $this->getTabs();

        if ($tabs && ($this->displaySingleTab || count($tabs) > 1)) {
            // Set the correct parameters
            $this->getCurrentTab();

            $tabRow = Html::create()->ul();

            foreach ($tabs as $tabId => $content) {

                $routeName = $this->requestInfo->getRouteName();
                $routeParams = $this->requestInfo->getRequestMatchedParams();
                $url = $this->menuSnippetHelper->getRouteUrl($routeName, $routeParams) . '?' . http_build_query($this->getParameterKeysFor($tabId));

                $li = $tabRow->li(['class' => $this->tabClass]);
                $link = $li->a($url, $content, ['class' => $this->tabLinkClass]);

                if ($this->currentTab == $tabId) {
                    $link->appendAttrib('class', $this->tabActiveClass);
                }
            }

            return $tabRow;
        } else {
            return null;
        }
    }

    /**
     * Return optionally the single parameter key which should left out for the default value,
     * but is added for all other tabs.
     *
     * @return mixed
     */
    protected function getParameterKey()
    {
        return null;
    }

    /**
     * Return the parameters that should be used for this tabId
     *
     * @param string $tabId
     * @return array
     */
    protected function getParameterKeysFor(string $tabId): array
    {
        $paramKey = $this->getParameterKey();

        if ($paramKey) {
            return array($paramKey => $tabId);
        }

        return [];
    }

    /**
     * Function used to fill the tab bar
     *
     * @return array tabId => label
     */
    abstract protected function getTabs(): array;
}