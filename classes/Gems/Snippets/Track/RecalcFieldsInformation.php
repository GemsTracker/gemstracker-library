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
class RecalcFieldsInformation extends \MUtil_Snippets_SnippetAbstract
{
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
        $seq = $this->getHtmlSequence();

        $seq->h2($this->_('Track field recalculation'));

        $ul = $seq->ul();
        $ul->li($this->_('Recalculates the values the fields should have.'));
        $ul->li($this->_('Couple existing appointments to tracks where an appointment field is not filled.'));
        $ul->li($this->_('Overwrite existing appointments to tracks e.g. when the filters have changed.'));
        $ul->li($this->_(
                'Checks the validity dates and times of unanswered tokens, using the current round settings.'
                ));

        $seq->pInfo($this->_(
                'Run this code when automatically calculated track fields have changed, when the appointment filters used by this track have changed or when the code has changed and the track must be adjusted.'
                ));
        $seq->pInfo($this->_(
                'If you do not run this code after changing track fields, then the old fields values remain as they were and only newly changed and newly created tracks will reflect the changes.'
                ));

        return $seq;
    }
}
