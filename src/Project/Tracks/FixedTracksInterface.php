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
 * Marker interface for Pulse Projects that use tracks that cannot be assigned by the user
 * (but are assigned by the system instead).
 *
 * @see \Gems\Project\Tracks\MultiTracksInterface
 * @see \Gems\Project\Tracks\SingleTrackInterface
 *
 * @package    Gems
 * @subpackage Project
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 * @deprecated since version 1.7.1 Stand Alone Survey engine no longer exitst
 */
interface FixedTracksInterface
{
    public function getTrackIds();
}