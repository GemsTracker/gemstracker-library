<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage SnippetsActions
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsActions\Browse;

use Gems\Snippets\ModelTableSnippet;
use Zalt\Model\MetaModellerInterface;
use Zalt\SnippetsActions\Browse\BrowseTableAction;
use Zalt\SnippetsActions\PostActionInterface;

/**
 *
 * @package    Gems
 * @subpackage SnippetsActions
 * @since      Class available since version 1.9.2
 */
class BrowseFilteredAction extends BrowseTableAction implements PostActionInterface
{
    protected array $_snippets = [
        ModelTableSnippet::class,
        ];

    /**
     * @var boolean Sets pagination on or off.
     */
    public bool $browse = true;

    public string $class = 'browser table';

    public array $defaultSearchData = [];

    public array $menuEditRoutes = ['edit'];

    public array $menuShowRoutes = ['show'];

    public MetaModellerInterface $model;
}