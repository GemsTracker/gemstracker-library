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
     * @var \Gems_Tracker_Engine_TrackEngineInterface
     */
    protected $trackEngine;

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->contentTitle = sprintf($this->_('Quick view %s track'), $this->trackEngine->getTrackName());
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return $this->trackEngine instanceof \Gems_Tracker_Engine_TrackEngineInterface;
    }
}
