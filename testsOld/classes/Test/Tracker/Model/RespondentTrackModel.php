<?php

namespace Test\Tracker\Model;

use Gems\Tracker\Model\RespondentTrackModel;
use UnitTestDBFixTrait;

/**
 * Test version of RespondentModel
 * 
 * Fixes issues because of lacking date field in sqlite
 *
 * @author Menno Dekker <menno.dekker@erasmusmc.nl>
 */
class RespondentTrackModel extends \Gems\Tracker\Model\RespondentTrackModel {
    
    use UnitTestDBFixTrait;
    
    public function getDateFields() {
        return [
            'gr2t_start_date' => 'date',
        ];
    }    
    
}