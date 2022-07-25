<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Token;

/**
 * Token snippet to return when a token snippet is required but the token does not
 * exist. It displays a message that the token was not found.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class TokenNotFoundSnippet extends \MUtil\Snippets\SnippetAbstract
{
    /**
     * Optional: id of the selected token to show
     *
     * Can be derived from $request or $token
     *
     * @var string
     */
    protected $tokenId;

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        if ($this->tokenId) {
            $this->addMessage(sprintf($this->_('Token %s not found.'), $this->tokenId));
        } else {
            $this->addMessage($this->_('No token specified.'));
        }
        return null;
    }
}
