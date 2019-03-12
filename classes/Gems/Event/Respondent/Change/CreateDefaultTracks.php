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
class CreateDefaultTracks extends \MUtil_Translate_TranslateableAbstract
    implements RespondentChangedEventInterface
{
    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems_Loader
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
                $this->_('Add all tracks with a trackcode containging "%s".'),
                $this->trackCode
                );
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

        $tracksData = $this->db->fetchAll("SELECT * FROM gems__tracks
            WHERE gtr_active = 1 AND
                CONCAT(' ', gtr_code, ' ') LIKE '% " . $this->trackCode . " %' AND
                gtr_organizations LIKE '%|" . $respondent->getOrganizationId() . "|%'
            ORDER BY gtr_track_name");

        // \MUtil_Echo::track($tracksData);

        $changes = false;

        foreach ($tracksData as $trackData) {
            $trackEngine = $tracker->getTrackEngine($trackData);
            if ($trackEngine instanceof \Gems_Tracker_Engine_TrackEngineInterface) {
                // \MUtil_Echo::track($trackEngine->getTrackCode(), count($respTracks));

                $create = true;
                foreach($respTracks as $respondentTrack) {
                    if (($respondentTrack instanceof \Gems_Tracker_RespondentTrack) &&
                            ($respondentTrack->getTrackId() == $trackEngine->getTrackId())) {
                        $create = false;
                        break;
                    }
                }

                // \MUtil_Echo::track($create);
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
