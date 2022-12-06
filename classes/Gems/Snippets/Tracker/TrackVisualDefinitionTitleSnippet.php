<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2019 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker;

use Gems\Snippets\Generic\ContentTitleSnippet;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2019 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.6
 */
class TrackVisualDefinitionTitleSnippet extends ContentTitleSnippet
{
    /**
     * Required: the engine of the current track
     *
     * @var \Gems\Tracker\Engine\TrackEngineInterface
     */
    protected $trackEngine;

    public function getHtmlOutput()
    {
        $this->contentTitle = sprintf($this->_('Quick view %s track'), $this->trackEngine->getTrackName());
        
        return parent::getHtmlOutput();
    }
}
