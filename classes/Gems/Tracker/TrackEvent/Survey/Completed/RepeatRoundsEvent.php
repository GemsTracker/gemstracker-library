<?php

/**
 *
 * @package    Gems
 * @subpackage Event\Survey\Completed
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2022, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Tracker\TrackEvent\Survey\Completed;

use Gems\Date\Period;
use Gems\Legacy\CurrentUserRepository;
use Gems\Tracker\Token;
use Gems\Tracker\TrackEvent\SurveyCompletedEventInterface;
use MUtil\Translate\Translator;

/**
 *
 * @package    Gems
 * @subpackage Event\Survey\Completed
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class RepeatRoundsEvent implements SurveyCompletedEventInterface
{
    protected int $currentUserId;

    public function __construct(protected Translator $translator, CurrentUserRepository $currentUserRepository)
    {
        $this->currentUserId = $currentUserRepository->getCurrentUser()->getUserId();
    }
    
    /**
     * @inheritDoc
     */
    public function getEventName(): string
    {
        return $this->translator->_('Repeat rounds on \'repeatRound\' with \'repeatUnit/Count\'.');
    }

    /**
     * @inheritDoc
     */
    public function processTokenData(Token $token): array
    {
        if (! $token->isCompleted()) {
            // Do not handel
            return [];
        }

        $answers = $token->getRawAnswers();
        $count = 0;
        if (isset($answers['repeatRound']) && 'Y' !== $answers['repeatRound']) {
            // Do not repeat, do nothing!
            return [];
        }
        if (isset($answers['repeatUnitCount'])) {
            $unit  = substr($answers['repeatUnitCount'], 0, 1);
            $count = intval(substr($answers['repeatUnitCount'], 1));
            
        } elseif (isset($answers['repeatCount'], $answers['repeatUnit'])) {
            $unit  = substr($answers['repeatUnit'], 0, 1);
            $count = intval($answers['repeatCount']);

        } elseif (isset($answers['repeatUnitCountother'])) {
            $unit  = 'D';
            $count = intval($answers['repeatUnitCountother']);
            
        } else {
            $unit = false;
        }
        
        $oldRoundDescription = trim($token->getRoundDescription());
        $matches = [];
        if (! preg_match('/(\d+)$/', $oldRoundDescription, $matches)) {
            // \MUtil\EchoOut\EchoOut::track($oldRoundDescription, $matches);
            throw new \Gems\Exception("RepeatRounds event called on round with description not ending in a number!");
            // Abort on no new description
            return [];
        }
        $oldRoundCount = intval($matches[1]);
        $newRoundCount = $oldRoundCount + 1;

        $newRoundDescription = 'RepeatRoundsEvent' . substr($oldRoundDescription, 0, -strlen((string)$oldRoundCount));
        $newOrder            = $token->getRoundOrder();
        $respondentTrack     = $token->getRespondentTrack();
        $validFrom           = $token->getCompletionTime();
        
        if ($unit) {
            $validUntil       = Period::applyPeriod($validFrom, $unit, $count);
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
        
        // \MUtil\EchoOut\EchoOut::track($oldRoundDescription, $newRoundDescription);
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
                // \MUtil\EchoOut\EchoOut::track($roundTokens, $newOrder);
                if (! isset($roundTokens[$newOrder])) {
                    // \MUtil\EchoOut\EchoOut::track($newValues);
                    $next->createReplacement($token->getComment(), $this->currentUserId, $newValues);
                }
            }
        }
        return [];
    }
}