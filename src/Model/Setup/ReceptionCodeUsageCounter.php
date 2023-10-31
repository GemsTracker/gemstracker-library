<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Usage
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model\Setup;

use Gems\Db\ResultFetcher;
use Gems\Usage\UsageCounterBasic;
use Zalt\Base\TranslatorInterface;

/**
 * @package    Gems
 * @subpackage Usage
 * @since      Class available since version 1.0
 */
class ReceptionCodeUsageCounter extends UsageCounterBasic
{
    public function __construct(ResultFetcher $resultFetcher, TranslatorInterface $translator)
    {
        parent::__construct($resultFetcher, $translator, 'grc_id_reception_code');

        $this->addTablePlural('gr2o_reception_code', 'gems__respondent2org', 'respondent', 'respondents');
        $this->addTablePlural('gr2t_reception_code', 'gems__respondent2track', 'track', 'tracks');
        $this->addTablePlural('gto_reception_code', 'gems__tokens', 'token', 'tokens');
    }
}