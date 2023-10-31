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
class ConsentUsageCounter extends \Gems\Usage\UsageCounterBasic
{
    public function __construct(ResultFetcher $resultFetcher, TranslatorInterface $translator)
    {
        parent::__construct($resultFetcher, $translator, 'gco_description');

        $this->addTablePlural('gr2o_consent', 'gems__respondent2org', 'respondent', 'respondents');
        $this->addTablePlural('glrc_old_consent', 'gems__log_respondent_consents', $this->getOldTopic(1), $this->getOldTopic(2));
        $this->addTablePlural('glrc_new_consent', 'gems__log_respondent_consents', $this->getNewTopic(1), $this->getNewTopic(2));
    }

    protected function getNewTopic($count): string
    {
        return $this->plural('consent log new consent', 'consent log new consents', $count);
    }

    protected function getOldTopic($count): string
    {
        return $this->plural('consent log old consent', 'consent log old consents', $count);
    }
}