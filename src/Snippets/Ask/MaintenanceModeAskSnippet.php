<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Ask
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Ask;

use Gems\Tracker\Token;
use Zalt\Snippets\TranslatableSnippetAbstract;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Ask
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 Aug 10, 2017 12:51:46 PM
 */
class MaintenanceModeAskSnippet extends TranslatableSnippetAbstract
{
    /**
     *
     * @var \Gems\Tracker\Token
     */
    protected $token;

    public function getHtmlOutput()
    {
        $html = $this->getHtmlSequence();

        $html->h3($this->_('System is in maintenance mode'));

        if ($this->token instanceof Token) {
            if ($this->token->isCompleted()) {
                if ($this->token->getNextUnansweredToken()) {
                    $html->p(['class' => 'info'])->strong($this->_('Your answers were processed correctly.'));
                    $html->p($this->_(
                            'Unfortunately this system has just entered maintenance mode so you cannot continue.'
                            ), ['class' => 'info']);
                    $html->p($this->_(
                            'Please try to continue at a later moment. Reuse your link or refresh this page.'
                            ), ['class' => 'info']);
                } else {
                    $html->p($this->_('This system has just entered maintenance mode.'), ['class' => 'info']);
                    $html->p($this->_(
                            'All your surveys have been processed correctly and there are no further questions.'
                            ), ['class' => 'info']);
                }
                return $html;
            }
        }

        $html->p($this->_('Unfortunately you cannot answer surveys while the system is in maintenance mode.'), ['class' => 'info']);
        $html->p($this->_('Please try again later.'), ['class' => 'info']);

        return $html;
    }
}
