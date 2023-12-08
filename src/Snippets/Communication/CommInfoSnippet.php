<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Snippets\Communication
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets\Communication;

use Zalt\Html\Html;

/**
 * @package    Gems
 * @subpackage Snippets\Communication
 * @since      Class available since version 1.0
 */
class CommInfoSnippet extends \Zalt\Snippets\TranslatableSnippetAbstract
{
    public function getHtmlOutput()
    {
        return Html::create('pInfo', $this->_('With automatic messaging jobs and a cron job on the server, messages can be sent without manual user action.'));
    }
}