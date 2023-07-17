<?php

/**
 *
 * @package    Gems
 * @subpackage Event\Survey\Completed
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2022, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Event\Survey\Completed;

use Gems\Date\Period;

/**
 *
 * @package    Gems
 * @subpackage Event\Survey\Completed
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class RepeatRoundsEvent extends \MUtil_Translate_TranslateableAbstract implements \Gems_Event_SurveyCompletedEventInterface
{
    /**
     * @var \Gems_User_User
     */
    protected $currentUser;
    
    /**
     * @inheritDoc
     */
    public function getEventName()
    {
        return $this->_('Repeat rounds on \'repeatRound\' with \'repeat[From][Unit|Count]\'.');
    }

    /**
     * @inheritDoc
     */
    public function processTokenData(\Gems_Tracker_Token $token)
    {
        if (! $token->isCompleted()) {
            // Do not handel
            return null;
        }

        $answers = $token->getRawAnswers();
        if (isset($answers['repeatRound']) && 'Y' !== $answers['repeatRound']) {
            // Do not repeat, do nothing!
            return null;
        }
        
        // From
        if (isset($answers['repeatFromUnitCount'])) {
            $fromUnit  = substr($answers['repeatFromUnitCount'], 0, 1);
            $fromCount = intval(substr($answers['repeatFromUnitCount'], 1));

        } elseif (isset($answers['repeatFromCount'], $answers['repeatFromUnit'])) {
            $fromUnit  = substr($answers['repeatFromUnit'], 0, 1);
            $fromCount = intval($answers['repeatFromCount']);

        } elseif (isset($answers['repeatFromUnitCountother'])) {
            $fromUnit  = 'D';
            $fromCount = intval($answers['repeatFromUnitCountother']);

        } else {
            $fromUnit = false;
        }
        
        // Until
        if (isset($answers['repeatUnitCount'])) {
            $untilUnit  = substr($answers['repeatUnitCount'], 0, 1);
            $untilCount = intval(substr($answers['repeatUnitCount'], 1));
            
        } elseif (isset($answers['repeatCount'], $answers['repeatUnit'])) {
            $untilUnit  = substr($answers['repeatUnit'], 0, 1);
            $untilCount = intval($answers['repeatCount']);

        } elseif (isset($answers['repeatUnitCountother'])) {
            $untilUnit  = 'D';
            $untilCount = intval($answers['repeatUnitCountother']);
            
        } else {
            $untilUnit = false;
        }
        
        $oldRoundDescription = trim($token->getRoundDescription());
        $matches = [];
        if (! preg_match('/(\d+)$/', $oldRoundDescription, $matches)) {
            // \MUtil_Echo::track($oldRoundDescription, $matches);
            throw new \Gems_Exception("RepeatRounds event called on round with description not ending in a number!");
            // Abort on no new description
            return null;
        }
        $oldRoundCount = intval($matches[1]);
        $newRoundCount = $oldRoundCount + 1;

        $newRoundDescription = substr($oldRoundDescription, 0, -strlen((string) $oldRoundCount)) . $newRoundCount;
        $newOrder            = $token->getRoundOrder();
        $respondentTrack     = $token->getRespondentTrack();
        $userId              = $this->currentUser->getUserId();

        if ($fromUnit) {
            $validFrom = Period::applyPeriod($token->getCompletionTime(), $fromUnit, $fromCount);
        } else {
            $validFrom = $token->getCompletionTime();
        }
        
        if ($untilUnit) {
            $validUntil       = Period::applyPeriod($validFrom, $untilUnit, $untilCount);
            $validUntilManual = 1;
        } else {
            $validUntil       = null;
            $validUntilManual = 0;
        }

        $allTokens   = $respondentTrack->getTokens();
        $roundTokens = [];
        foreach ($allTokens as $next) {
            $roundTokens[$next->getRoundOrder()] = $next->getTokenId();
        }
        
        // \MUtil_Echo::track($oldRoundDescription, $newRoundDescription);
        foreach ($allTokens as $next) {
            if ($next->getReceptionCode()->isSuccess() && ($next->getRoundDescription() == $oldRoundDescription)) {
                $newValues = [
                    'gto_id_round'           => 0,
                    'gto_round_order'        => ++$newOrder,
                    'gto_round_description'  => $newRoundDescription,
                    'gto_valid_from'         => $validFrom,
                    'gto_valid_from_manual'  => 1,
                    'gto_valid_until'        => $validUntil,
                    'gto_valid_until_manual' => $validUntilManual,
                    'gto_mail_sent_date'     => null,
                    'gto_mail_sent_num'      => 0,
                    ];
                
                // Check token exists already
                // \MUtil_Echo::track($roundTokens, $newOrder);
                if (! isset($roundTokens[$newOrder])) {
                    // \MUtil_Echo::track($newValues);
                    $next->createReplacement($token->getComment(), $userId, $newValues);
                }
            }
        }
    }
}