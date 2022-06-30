<?php

namespace Test\Tracker\Model;

use Gems_Tracker_Model_RespondentTrackModel;
use UnitTestDBFixTrait;

/**
 * Test version of RespondentModel
 * 
 * Fixes issues because of lacking date field in sqlite
 *
 * @author Menno Dekker <menno.dekker@erasmusmc.nl>
 */
class RespondentTrackModel extends Gems_Tracker_Model_RespondentTrackModel {
    
    use UnitTestDBFixTrait;
    
    public function getDateFields() {
        return [
            'gr2t_start_date' => 'date',
        ];
    }    
    
}