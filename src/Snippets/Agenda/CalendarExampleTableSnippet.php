<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @license    New BSD License
 */

namespace Gems\Snippets\Agenda;

use Zalt\Html\TableElement;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @license    New BSD License
 * @since      Class available since version 2.x
 */
class CalendarExampleTableSnippet extends CalendarTableSnippet
{
    /**
     * Don't add paginator controls, this snippet shows only a set of examples.
     * The override is required because browse is set to true in the parent class.
     */
    protected function addPaginator(TableElement $table, int $count, int $page, int $items)
    {
    }
}
