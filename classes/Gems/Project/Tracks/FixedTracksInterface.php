<?php

/**
 *
 * @package    Gems
 * @subpackage Project
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: FixedTracksInterface.php 213 2011-11-14 17:51:04Z matijsdejong $
 */

/**
 * Marker interface for Pulse Projects that use tracks that cannot be assigned by the user
 * (but are assigned by the system instead).
 *
 * @see \Gems_Project_Tracks_MultiTracksInterface
 * @see \Gems_Project_Tracks_SingleTrackInterface
 *
 * @package    Gems
 * @subpackage Project
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 * @deprecated since version 1.7.1 Stand Alone Survey engine no longer exitst
 */
interface Gems_Project_Tracks_FixedTracksInterface
{
    public function getTrackIds();
}