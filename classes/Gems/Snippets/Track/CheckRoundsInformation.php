<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Track
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Track;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Track
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Mar 21, 2016 12:17:12 PM
 */
class CheckRoundsInformation extends \MUtil\Snippets\SnippetAbstract
{
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
        $seq = $this->getHtmlSequence();

        $seq->h2($this->_('Checks'));

        $ul = $seq->ul();
        $ul->li($this->_('Updates existing token description and order to the current round description and order.'));
        $ul->li($this->_('Updates the survey of unanswered tokens when the round survey was changed.'));
        $ul->li($this->_('Removes unanswered tokens when the round is no longer active.'));
        $ul->li($this->_('Creates new tokens for new rounds.'));
        $ul->li($this->_(
                'Checks the validity dates and times of unanswered tokens, using the current round settings.'
                ));

        $seq->pInfo($this->_(
                'Run this code when a track has changed or when the code has changed and the track must be adjusted.'
                ));
        $seq->pInfo($this->_(
                'If you do not run this code after changing a track, then the old tracks remain as they were and only newly created tracks will reflect the changes.'
                ));

        return $seq;
    }
}
