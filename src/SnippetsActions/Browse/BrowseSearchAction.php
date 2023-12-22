<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage SnippetsActions
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsActions\Browse;

use Gems\Snippets\AutosearchFormSnippet;
use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\Generic\CurrentButtonRowSnippet;
use Gems\SnippetsActions\ButtonRowActiontrait;
use Gems\SnippetsActions\ContentTitleActionTrait;

/**
 *
 * @package    Gems
 * @subpackage SnippetsActions
 * @since      Class available since version 1.9.2
 */
class BrowseSearchAction extends BrowseFilteredAction
{
    use ButtonRowActiontrait;
    use ContentTitleActionTrait;
    
    protected array $_startSnippets = [
        ContentTitleSnippet::class,
        AutosearchFormSnippet::class,
    ];

    protected array $_stopSnippets = [
        CurrentButtonRowSnippet::class,
    ];

    /**
     * Array of model name => empty text to allow adding select boxes in a flexible way using the model as source for options
     *
     * When key is numeric, the value is added to the elements as-is
     *
     * @var array
     */
    public array $searchFields = [];

    /**
     * @var array The raw data the search is based on
     */
    public array $searchData = [];

    public function appendStopSnippet(string $snippetClass)
    {
        $this->_stopSnippets[] = $snippetClass;
    }

    public function getSnippetClasses() : array
    {
        return array_merge(
            $this->_startSnippets,
            parent::getSnippetClasses(),
            $this->_stopSnippets
        );
    }

    /**
     * Overwrite start snippets
     *
     * @param array $snippets
     * @return void
     */
    public function setStartSnippets(array $snippets): void
    {
        $this->_startSnippets = $snippets;
    }

    /**
     * Overwrite stop snippets
     *
     * @param array $snippets
     * @return void
     */
    public function setStopSnippets(array $snippets): void
    {
        $this->_stopSnippets = $snippets;
    }
}