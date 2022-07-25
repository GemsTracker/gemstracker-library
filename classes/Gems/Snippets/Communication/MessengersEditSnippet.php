<?php

namespace Gems\Snippets\Communication;

class MessengersEditSnippet extends \Gems\Snippets\ModelFormSnippetGeneric
{
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $htmlDiv = parent::getHtmlOutput($view);

        $infoDiv = \MUtil\Html::div(['class' => 'alert alert-info', 'role' => "alert"]);

        $infoDiv->h4($this->_('Instructions'));
        $infoDiv->pInfo($this->_('HTTP API messengers can be configured in /var/settings/communication.php'));

        $htmlDiv[] = $infoDiv;

        return $htmlDiv;
    }
}
