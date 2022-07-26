<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Track
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Survey;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Track
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Mar 21, 2016 12:17:12 PM
 */
class CheckAnswersInformation extends \MUtil\Snippets\SnippetAbstract
{
    /**
     *
     * @var Describe which tokens will be checked
     */
    protected $itemDescription;

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

        $seq->pInfo($this->_(
                'Check tokens for being answered or not, reruns survey and round event code on completed tokens and recalculates the start and end times of all tokens in tracks that have completed tokens.'
                ));
        $seq->pInfo($this->_(
                'Run this code when survey result fields, survey or round events or the event code has changed or after bulk changes in a survey source.'
                ));

        if ($this->itemDescription) {
            $seq->pInfo($this->itemDescription);
        }

        return $seq;
    }
}
