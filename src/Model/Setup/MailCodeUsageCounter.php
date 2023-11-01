<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Model\Setup
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model\Setup;

use Gems\Db\ResultFetcher;
use Zalt\Base\TranslatorInterface;

/**
 * @package    Gems
 * @subpackage Model\Setup
 * @since      Class available since version 1.0
 */
class MailCodeUsageCounter extends \Gems\Usage\UsageCounterBasic
{
    public function __construct(ResultFetcher $resultFetcher, TranslatorInterface $translator)
    {
        parent::__construct($resultFetcher, $translator, 'gmc_id');

        $this->addTablePlural('gr2o_mailable', 'gems__respondent2org', 'respondent', 'respondents');
        $this->addTablePlural('gr2t_mailable', 'gems__respondent2track', 'track', 'tracks');
        $this->addTablePlural('gsu_mail_code', 'gems__surveys', 'survey', 'surveys');
    }
}