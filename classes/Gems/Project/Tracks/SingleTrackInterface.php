<?php

/**
 *
 * @package    Gems
 * @subpackage Project
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Marker interface for Pulse Projects using only a single track
 *
 * These projects will autmatically be an instance of
 * \Gems_Project_Tracks_TracksOnlyInterface (we assume until
 * proven wrong)
 *
 * @see \Gems_Project_Tracks_MultiTracksInterface
 *
 * @package    Gems
 * @subpackage Project
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
interface Gems_Project_Tracks_SingleTrackInterface
{
    /**
     * Return the "single" track id
     *
     * @return int
     */
    public function getTrackId();
}