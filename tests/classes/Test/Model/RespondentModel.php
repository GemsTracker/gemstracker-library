<?php

namespace Test\Model;

use Gems_Model_RespondentModel;
use UnitTestDBFixTrait;

/**
 * Test version of RespondentModel
 * 
 * Fixes issues because of lacking date field in sqlite
 *
 * @author Menno Dekker <menno.dekker@erasmusmc.nl>
 */
class RespondentModel extends Gems_Model_RespondentModel {
    
    use UnitTestDBFixTrait;
    
    public function getDateFields() {
        return [
            'grs_birthday' => 'date',
        ];
    }    
    
}