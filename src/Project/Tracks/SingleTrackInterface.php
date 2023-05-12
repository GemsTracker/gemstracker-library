<?php

/**
 *
 * @package    Gems
 * @subpackage Project
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Project\Tracks;

/**
 * Marker interface for Pulse Projects using only a single track
 *
 * These projects will autmatically be an instance of
 * \Gems\Project\Tracks\TracksOnlyInterface (we assume until
 * proven wrong)
 *
 * @see \Gems\Project\Tracks\MultiTracksInterface
 *
 * @package    Gems
 * @subpackage Project
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
interface SingleTrackInterface
{
    /**
     * Return the "single" track id
     *
     * @return int
     */
    public function getTrackId();
}