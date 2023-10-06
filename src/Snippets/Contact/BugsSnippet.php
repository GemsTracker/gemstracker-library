<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Contact
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Contact;

use Gems\Project\ProjectSettings;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetOptions;
use Zalt\Snippets\TranslatableSnippetAbstract;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Contact
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 2.0
 */
class BugsSnippet extends TranslatableSnippetAbstract
{
    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo, 
        TranslatorInterface $translate,
        protected ProjectSettings $project
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate);
    }

    /**
     * Create the snippets content
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput()
    {
        $html = $this->getHtmlSequence();

        $html->h3($this->_('About bug reporting'));
        $html->pInfo()->sprintf($this->_('%s uses the Issue Tracker at %s for reporting and solving issues.'), $this->project->getName())
            ->a($this->project->getBugsUrl(), ['rel' => 'external', 'target' => 'pulse_bugs']);
        $html->pInfo($this->_('To help us to quickly solve bugs we request you read the suggestions below.'));

        $div = $html->div(['class' => 'indent']);
        $div->h4($this->_('Make an account on GitHub'));
        $div->pInfo($this->_('You need a (free) account on GitHub to report issues.'));

        $div->h4($this->_('Report each bug by itself'));
        $div->pInfo($this->_('Every bug has to be assigned to someone to solve it.'), ' ',
                $this->_('Combining multiple problems in a single issue can obstruct this process.'));

        $div->h4($this->_('Add a link'));
        $div->pInfo($this->_('If a problem is on a specific page, copy the address of the page from the addressbar of your browser.'));

        $div->h4($this->_('Add a screenshot'));
        $div->pInfo($this->_('Usually a screenshot helps us to solve the problem quicker.'));
        $ul = $div->ul();
        $li = $ul->li($this->_('Press'), ' ');
        $li->append()->span('[Alt]^[Print Screen]', ['style' => 'font-family: monospace']);
        $ul->li($this->_('Open Paint or another drawing package or even Word.'));
        $ul->li($this->_('Paste the clipboard and save the result as a file.'));
        $ul->li($this->_('Add this file to the issue.'));

        $html->h3($this->_('Report a bug'));
        $html->pInfo($this->_('You can find the Issue Tracker here:'), ' ')
            ->a($this->project->getBugsUrl(), ['rel' => 'external', 'target' => 'pulse_bugs']);

        return $html;
    }
}
