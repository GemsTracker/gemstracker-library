<?php

namespace Gems\Snippets\Token;

use Zalt\Snippets\MessageableSnippetAbstract;

/**
 * Token snippet to return when a completed token is required but the selected
 * token is not completed.
 */
class TokenNotCompletedSnippet extends MessageableSnippetAbstract
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
     * Create the snippets content.
     *
     * @return mixed Something that can be rendered
     */
    public function getHtmlOutput()
    {
        $messenger = $this->getMessenger();
        if ($this->tokenId) {
            $messenger->addMessage(sprintf($this->_('Token %s not completed.'), $this->tokenId));
        } else {
            $messenger->addMessage($this->_('No token specified.'));
        }
    }
}
