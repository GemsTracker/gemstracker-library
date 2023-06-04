<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets;

use Zalt\Snippets\TranslatableSnippetAbstract;

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @since      Class available since version 1.9.2
 */
class InfoSnippet extends TranslatableSnippetAbstract 
{
    /**
     * @var string
     */
    protected $check;
    
    public function getHtmlOutput()
    {
        $html = $this->getHtmlSequence();
        
        $html->h1($this->_('GemsTracker 2.0'));
        $html->p('Info concerning GT 2.0!');
        $html->p($this->check);
        
        return $html; 
    }

}