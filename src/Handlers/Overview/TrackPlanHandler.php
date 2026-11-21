<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Handlers\Overview
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Handlers\Overview;

use Gems\Handlers\GemsHandler;
use Gems\SnippetsActions\Browse\BrowseFilteredAction;
use Gems\SnippetsActions\Browse\BrowseSearchAction;
use Gems\SnippetsActions\Export\ExportAction;
use Gems\SnippetsActions\Show\ShowAsTableAction;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModellerInterface;
use Zalt\SnippetsActions\SnippetActionInterface;

/**
 * @package    Gems
 * @subpackage Handlers\Overview
 * @since      Class available since version 1.0
 */
class TrackPlanHandler extends GemsHandler
{
    public static $actions = [
        'autofilter' => BrowseFilteredAction::class,
        'index'      => BrowseSearchAction::class,
        'show'       => ShowAsTableAction::class,
        'export'     => ExportAction::class,
    ];

    protected DataReaderInterface|null $model = null;

    /**
     * @inheritDoc
     */
    protected function getModel(SnippetActionInterface $action): MetaModellerInterface
    {
        if (!$this->model) {
        }
        return $this->model;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return string
     */
    public function getTopic($count = 1): string
    {
        return $this->_('Track status');
    }

}