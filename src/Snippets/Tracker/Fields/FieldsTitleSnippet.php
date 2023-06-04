<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker\Fields;

use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Tracker\Engine\TrackEngineInterface;
use MUtil\Translate\Translator;
use Zalt\Base\RequestInfo;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 9-sep-2015 18:36:59
 */
class FieldsTitleSnippet extends ContentTitleSnippet
{

    /**
     * @var TrackEngineInterface
     */
    protected $trackEngine;

    public function getHtmlOutput()
    {
        $this->contentTitle = sprintf($this->_('Fields in %s track'), $this->trackEngine->getTrackName());
        
        return parent::getHtmlOutput();
    }
}
