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

namespace Gems\Tracker\TrackEvent\Respondent\Change;

use Gems\Legacy\CurrentUserRepository;
use Gems\Tracker;
use Gems\Tracker\Respondent;
use Gems\Tracker\RespondentTrack;
use Gems\Tracker\TrackEvent\RespondentChangedEventInterface;
use MUtil\Translate\Translator;

/**
 *
 *
 * @package    Gems
 * @subpackage Event\Respondent
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Sep 6, 2016 3:32:54 PM
 */
class RecalculateTracks implements RespondentChangedEventInterface
{
    public int $currentUserId;

    public function __construct(protected Tracker $tracker, protected Translator $translator, CurrentUserRepository $currentUserRepository)
    {
        $this->currentUserId = $currentUserRepository->getCurrentUserId();
    }

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName(): string
    {
        return $this->translator->_('Recalculate all respondent tracks');
    }

    /**
     * Process the respondent and return true when data has changed.
     *
     * The event has to handle the actual storage of the changes.
     *
     * @param Respondent $respondent
     * @param int $userId The current user
     * @return boolean True when something changed
     */
    public function processChangedRespondent(Respondent $respondent): bool
    {
        $changes    = 0;
        $respTracks = $this->tracker->getRespondentTracks($respondent->getId(), $respondent->getOrganizationId());

        foreach($respTracks as $respondentTrack) {
            if ($respondentTrack instanceof RespondentTrack) {
                $changes += $respondentTrack->checkTrackTokens($this->currentUserId);
            }
        }

        return (boolean) $changes;
    }
}
