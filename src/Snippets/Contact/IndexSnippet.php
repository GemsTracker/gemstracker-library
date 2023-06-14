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
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
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
class IndexSnippet extends TranslatableSnippetAbstract
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

        $html->h3($this->_('Contact'));

        $html->h4(sprintf($this->_('The %s project'), $this->project->getName()));

        return $html;
    }
}
