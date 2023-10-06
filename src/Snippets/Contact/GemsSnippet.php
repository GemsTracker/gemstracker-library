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
class GemsSnippet extends TranslatableSnippetAbstract
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

        $html->h3()->sprintf($this->_('About %s'), $this->_('GemsTracker'));
        $html->pInfo($this->_(
                'GemsTracker (GEneric Medical Survey Tracker) is a software package for (complex) distribution of questionnaires and forms during clinical research and for quality registration in healthcare.'));
        $html->pInfo()->sprintf(
                $this->_('%s is a project built using GemsTracker as a foundation.'),
                $this->project->getName());
        $html->pInfo()->sprintf($this->_('GemsTracker is an open source project hosted on %s.'))
                ->a('https://github.com/GemsTracker/gemstracker-library',
                    'GitHub',
                    ['rel' => 'external', 'target' => 'sourceforge']);
        $html->pInfo()->sprintf($this->_('More information about GemsTracker is available on the %s website.'))
                ->a('http://gemstracker.org/',
                    'GemsTracker.org',
                    ['rel' => 'external', 'target' => 'gemstracker']);

        return $html;
    }
}
