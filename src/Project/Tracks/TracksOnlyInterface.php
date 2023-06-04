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
 * Marker interface for Pulse Projects NOT allowing the single survey tracks
 * allowed by \Gems\Project\Tracks\StandAloneSurveysInterface (a nd that are just
 * shells for assinging a single survey).
 *
 * @see \Gems\Project\Tracks\StandAloneSurveysInterface
 *
 * @package    Gems
 * @subpackage Project
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 * @deprecated since version 1.7.1 Stand Alone Survey engine no longer exitst
 */
interface TracksOnlyInterface
{}