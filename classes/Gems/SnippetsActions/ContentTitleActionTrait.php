<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage SnippetsActions
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsActions;

/**
 *
 * @package    Gems
 * @subpackage SnippetsActions
 * @since      Class available since version 1.9.2
 */
trait ContentTitleActionTrait
{
    /**
     * @var string Title to display in the ContentTitle snippet
     */
    public string $contentTitle = '';

    /**
     * @var string Tagname for title
     */
    public string $tagName = 'h2';
}