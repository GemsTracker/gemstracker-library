<?php

declare(strict_types=1);

namespace Gems\Model;

use Gems\Db\ResultFetcher;
use Gems\Usage\UsageCounterBasic;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Where;
use Zalt\Base\TranslatorInterface;

class ConditionUsageCounter extends UsageCounterBasic
{
    public function __construct(ResultFetcher $resultFetcher, TranslatorInterface $translator) {
        parent::__construct($resultFetcher, $translator, 'gcon_id');

        $this->addTablePlural('gro_condition', 'gems__rounds', 'round', 'rounds');

        $select = (new Select('gems__conditions'))
            ->columns(['gcon_id'])
            ->where(function (Where $where) {
                return $where
                    ->nest()
                    ->or->equalTo('gcon_condition_text1', '?')
                    ->or->equalTo('gcon_condition_text2', '?')
                    ->or->equalTo('gcon_condition_text3', '?')
                    ->or->equalTo('gcon_condition_text4', '?')
                    ->unnest()
                    ->nest()
                    ->or->like('gcon_class', '%AndCondition')
                    ->or->like('gcon_class', '%OrCondition')
                    ->unnest();
            });


        $this->addCustomQueryPlural($select, 'condition', 'conditions');
    }
}