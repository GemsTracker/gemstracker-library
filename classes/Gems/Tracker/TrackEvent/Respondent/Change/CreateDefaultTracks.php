<?php

/**
 *
 * @package    Gems
 * @subpackage Event\Respondent\Change
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Tracker\TrackEvent\Respondent\Change;

use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Tracker;
use Gems\Tracker\Respondent;
use Gems\Tracker\RespondentTrack;
use Gems\Tracker\TrackEvent\RespondentChangedEventInterface;
use MUtil\Translate\Translator;

/**
 *
 * @package    Gems
 * @subpackage Event\Respondent\Change
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 12-Mar-2019 15:29:10
 */
class CreateDefaultTracks implements RespondentChangedEventInterface
{
    protected int $currentUserId;

    /**
     * The code to check on
     *
     * @var String
     */
    protected string $trackCode = 'default';

    public function __construct(protected Tracker $tracker, protected ResultFetcher $resultFetcher, protected Translator $translator, CurrentUserRepository $currentUserRepository)
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
        return sprintf(
                $this->translator->_('Add all tracks with containing the trackcode "%s".'),
                $this->trackCode
                );
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
    public function processChangedRespondent(Respondent $respondent): bool
    {
        $changes    = 0;
        $respTracks = $this->tracker->getRespondentTracks($respondent->getId(), $respondent->getOrganizationId());

        $tracksData = $this->resultFetcher->fetchAll("SELECT * FROM gems__tracks
            WHERE gtr_active = 1 AND
                CONCAT(' ', gtr_code, ' ') LIKE '% " . $this->trackCode . " %' AND
                gtr_organizations LIKE '%|" . $respondent->getOrganizationId() . "|%'
            ORDER BY gtr_track_name");

        // \MUtil\EchoOut\EchoOut::track($tracksData);

        $changes = false;

        foreach ($tracksData as $trackData) {
            $trackEngine = $this->tracker->getTrackEngine($trackData);
            if ($trackEngine instanceof \Gems\Tracker\Engine\TrackEngineInterface) {
                // \MUtil\EchoOut\EchoOut::track($trackEngine->getTrackCode(), count($respTracks));

                $create = true;
                foreach($respTracks as $respondentTrack) {
                    if (($respondentTrack instanceof RespondentTrack) &&
                            ($respondentTrack->getTrackId() == $trackEngine->getTrackId())) {
                        $create = false;
                        break;
                    }
                }

                // \MUtil\EchoOut\EchoOut::track($create);
                if ($create) {
                    $changes = true;
                    $this->tracker->createRespondentTrack(
                            $respondent->getId(),
                            $respondent->getOrganizationId(),
                            $trackEngine->getTrackId(),
                            $this->currentUserId,
                            );
                }
            }
        }

        return $changes;
    }
}
