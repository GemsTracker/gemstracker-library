<?php

/**
 *
 * @package    Gems
 * @subpackage Event\Respondent\Change
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Event\Respondent\Change;

use Gems\Event\RespondentChangedEventInterface;

/**
 *
 * @package    Gems
 * @subpackage Event\Respondent\Change
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 12-Mar-2019 15:29:10
 */
class CreateDefaultTracks extends \MUtil\Translate\TranslateableAbstract
    implements RespondentChangedEventInterface
{
    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * The code to check on
     *
     * @var String
     */
    protected $trackCode = 'default';

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName()
    {
        return sprintf(
                $this->_('Add all tracks with containing the trackcode "%s".'),
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
    public function processChangedRespondent(\Gems\Tracker\Respondent $respondent)
    {
        $changes    = 0;
        $tracker    = $this->loader->getTracker();
        $respTracks = $tracker->getRespondentTracks($respondent->getId(), $respondent->getOrganizationId());
        $userId     = $this->currentUser->getUserId();

        $tracksData = $this->db->fetchAll("SELECT * FROM gems__tracks
            WHERE gtr_active = 1 AND
                CONCAT(' ', gtr_code, ' ') LIKE '% " . $this->trackCode . " %' AND
                gtr_organizations LIKE '%|" . $respondent->getOrganizationId() . "|%'
            ORDER BY gtr_track_name");

        // \MUtil\EchoOut\EchoOut::track($tracksData);

        $changes = false;

        foreach ($tracksData as $trackData) {
            $trackEngine = $tracker->getTrackEngine($trackData);
            if ($trackEngine instanceof \Gems\Tracker\Engine\TrackEngineInterface) {
                // \MUtil\EchoOut\EchoOut::track($trackEngine->getTrackCode(), count($respTracks));

                $create = true;
                foreach($respTracks as $respondentTrack) {
                    if (($respondentTrack instanceof \Gems\Tracker\RespondentTrack) &&
                            ($respondentTrack->getTrackId() == $trackEngine->getTrackId())) {
                        $create = false;
                        break;
                    }
                }

                // \MUtil\EchoOut\EchoOut::track($create);
                if ($create) {
                    $changes = true;
                    $tracker->createRespondentTrack(
                            $respondent->getId(),
                            $respondent->getOrganizationId(),
                            $trackEngine->getTrackId(),
                            $userId
                            );
                }
            }
        }

        return $changes;
    }
}
