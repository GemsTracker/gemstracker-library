<?php

declare(strict_types=1);

/**
 * @package    GemsTest
 * @subpackage TestData\General
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace GemsTest\TestData\General\Condition;

use Gems\Db\Migration\SeedAbstract;

/**
 * @package    GemsTest
 * @subpackage TestData\General
 * @since      Class available since version 1.0
 */
class TestConditionSeed extends SeedAbstract
{
    public function __invoke(): array
    {
        return [
            'gems__conditions' => [
                [
                    'gcon_id' => 1000,
                    'gcon_type' => 'Round',
                    'gcon_class' => 'Gems\\Condition\\Round\\RepeatLessCondition',
                    'gcon_name' => 'tracksurvey',

                    'gcon_condition_text1' => 'tracksurvey',
                    'gcon_condition_text2' => 'D',
                    'gcon_condition_text3' => '20',
                    'gcon_condition_text4' => null,

                    'gcon_active' => 1,
                    'gcon_changed_by' => 1,
                    'gcon_created_by' => 1,
                ],
                [
                    'gcon_id' => 1001,
                    'gcon_type' => 'Round',
                    'gcon_class' => 'Gems\\Condition\\Round\\RepeatLessCondition',
                    'gcon_name' => 'track',

                    'gcon_condition_text1' => 'track',
                    'gcon_condition_text2' => 'D',
                    'gcon_condition_text3' => '20',
                    'gcon_condition_text4' => null,

                    'gcon_active' => 1,
                    'gcon_changed_by' => 1,
                    'gcon_created_by' => 1,
                ],
                [
                    'gcon_id' => 1002,
                    'gcon_type' => 'Round',
                    'gcon_class' => 'Gems\\Condition\\Round\\RepeatLessCondition',
                    'gcon_name' => 'trackcode',

                    'gcon_condition_text1' => 'trackcode',
                    'gcon_condition_text2' => 'D',
                    'gcon_condition_text3' => '20',
                    'gcon_condition_text4' => null,

                    'gcon_active' => 1,
                    'gcon_changed_by' => 1,
                    'gcon_created_by' => 1,
                ],
                [
                    'gcon_id' => 1003,
                    'gcon_type' => 'Round',
                    'gcon_class' => 'Gems\\Condition\\Round\\RepeatLessCondition',
                    'gcon_name' => 'code',

                    'gcon_condition_text1' => 'code',
                    'gcon_condition_text2' => 'D',
                    'gcon_condition_text3' => '20',
                    'gcon_condition_text4' => null,

                    'gcon_active' => 1,
                    'gcon_changed_by' => 1,
                    'gcon_created_by' => 1,
                ],
            ],
        ];
    }

}