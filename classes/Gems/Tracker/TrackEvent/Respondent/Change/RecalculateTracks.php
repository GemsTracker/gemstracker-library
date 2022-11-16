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
class RecalculateTracks extends \MUtil\Translate\TranslateableAbstract
    implements RespondentChangedEventInterface
{
    /**
     *
     * @var \Gems\User\User
     */
    public $currentUser;

    /**
     *
     * @var \Gems\Loader
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
     * @param \Gems\Tracker\Respondent $respondent
     * @param int $userId The current user
     * @return boolean True when something changed
     */
    public function processChangedRespondent(\Gems\Tracker\Respondent $respondent)
    {
        $changes    = 0;
        $tracker    = $this->loader->getTracker();
        $respTracks = $tracker->getRespondentTracks($respondent->getId(), $respondent->getOrganizationId());
        $userId     = $this->currentUser->getUserId();

        foreach($respTracks as $respondentTrack) {
            if ($respondentTrack instanceof \Gems\Tracker\RespondentTrack) {
                $changes += $respondentTrack->checkTrackTokens($userId);
            }
        }
        // \MUtil\EchoOut\EchoOut::track('Hi there! ' . $changes);

        return (boolean) $changes;
    }
}
