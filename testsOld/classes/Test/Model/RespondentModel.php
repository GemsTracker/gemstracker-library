<?php

namespace Test\Model;

use Gems\Model\RespondentModel;
use UnitTestDBFixTrait;

/**
 * Test version of RespondentModel
 * 
 * Fixes issues because of lacking date field in sqlite
 *
 * @author Menno Dekker <menno.dekker@erasmusmc.nl>
 */
class RespondentModel extends \Gems\Model\RespondentModel {
    
    use UnitTestDBFixTrait;
    
    public function getDateFields() {
        return [
            'grs_birthday' => 'date',
        ];
    }    
    
}