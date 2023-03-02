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

    public function getSnippetClasses() : array
    {
        return array_merge(
            $this->_startSnippets,
            parent::getSnippetClasses(),
            $this->_stopSnippets
        );
    }
}