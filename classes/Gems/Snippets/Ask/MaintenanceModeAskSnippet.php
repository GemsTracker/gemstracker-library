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

/**
 *
 * @package    Gems
 * @subpackage Snippets\Ask
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 Aug 10, 2017 12:51:46 PM
 */
class MaintenanceModeAskSnippet extends \MUtil_Snippets_SnippetAbstract
{
    /**
     *
     * @var \Gems_Tracker_Token
     */
    protected $token;

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $html = $this->getHtmlSequence();

        $html->h3($this->_('System is in maintenance mode'));

        if ($this->token instanceof \Gems_Tracker_Token) {
            if ($this->token->isCompleted()) {
                if ($this->token->getNextUnansweredToken()) {
                    $html->pInfo()->strong($this->_('Your answers were processed correctly.'));
                    $html->pInfo($this->_(
                            'Unfortunately this system has just entered maintenance mode so you cannot continue.'
                            ));
                    $html->pInfo($this->_(
                            'Please try to continue at a later moment. Reuse your link or refresh this page.'
                            ));
                } else {
                    $html->pInfo($this->_('This system has just entered maintenance mode.'));
                    $html->pInfo($this->_(
                            'All your surveys have been processed correctly and there are no further questions.'
                            ));
                }
                return $html;
            }
        }

        $html->pInfo($this->_('Unfortunately you cannot answer surveys while the system is in maintenance mode.'));
        $html->pInfo($this->_('Please try again later.'));

        return $html;
    }
}
