<?php

namespace Gems\Snippets\Communication;

use Gems\Snippets\ModelFormSnippet;
use Zalt\Html\Html;

class MessengersEditSnippet extends ModelFormSnippet
{
    public function getHtmlOutput()
    {
        $htmlDiv = parent::getHtmlOutput();

        $infoDiv = Html::div([
            'class' => 'alert alert-info',
            'role' => 'alert',
        ]);

        $infoDiv->h4($this->_('Instructions'));
        $infoDiv->pInfo($this->_('HTTP API messengers can be configured in /var/settings/communication.php'));

        $htmlDiv[] = $infoDiv;

        return $htmlDiv;
    }
}
