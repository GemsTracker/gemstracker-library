<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Tracker\Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Tracker\Model;

use Gems\Db\ResultFetcher;
use Zalt\Base\TranslatorInterface;

/**
 * @package    Gems
 * @subpackage Tracker\Model
 * @since      Class available since version 1.0
 */
class TrackUsageCounter extends \Gems\Usage\UsageCounterBasic
{
    public function __construct(ResultFetcher $resultFetcher, TranslatorInterface $translator)
    {
        parent::__construct($resultFetcher, $translator, 'gtr_id_track');

        $this->addTablePlural('gtf_id_track', 'gems__track_fields', 'track field', 'track fields');
        $this->addTablePlural('gtap_id_track', 'gems__track_appointments', 'appointment field', 'appointment fields');
        $this->addTablePlural('gro_id_track', 'gems__rounds', 'round', 'rounds');
        $this->addTablePlural('gr2t_id_track', 'gems__respondent2track', 'respondent track', 'respondent tracks');
    }
}