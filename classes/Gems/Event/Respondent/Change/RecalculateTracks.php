<?php

/**
 *
 *
 * @package    Gems
 * @subpackage Event\Respondent
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Event\Respondent\Change;

use Gems\Event\RespondentChangedEventInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Event\Respondent
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Sep 6, 2016 3:32:54 PM
 */
class RecalculateTracks extends \MUtil_Translate_TranslateableAbstract
    implements RespondentChangedEventInterface
{
    /**
     *
     * @var \Gems_User_User
     */
    public $currentUser;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName()
    {
        return $this->_('Recalculate all respondent tracks');
    }

    /**
     * Process the respondent and return true when data has changed.
     *
     * The event has to handle the actual storage of the changes.
     *
     * @param \Gems_Tracker_Respondent $respondent
     * @param int $userId The current user
     * @return boolean True when something changed
     */
    public function processChangedRespondent(\Gems_Tracker_Respondent $respondent)
    {
        $changes    = 0;
        $tracker    = $this->loader->getTracker();
        $respTracks = $tracker->getRespondentTracks($respondent->getId(), $respondent->getOrganizationId());
        $userId     = $this->currentUser->getUserId();

        foreach($respTracks as $respondentTrack) {
            if ($respondentTrack instanceof \Gems_Tracker_RespondentTrack) {
                $changes += $respondentTrack->checkTrackTokens($userId);
            }
        }
        \MUtil_Echo::track('Hi there! ' . $changes);

        return (boolean) $changes;
    }
}
