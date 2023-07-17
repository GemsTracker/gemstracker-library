<?php

namespace Gems\Repository;

use Gems\Db\CachedResultFetcher;
use Gems\Translate\CachedDbTranslationRepository;

class MailRepository
{
    public function __construct(
        protected readonly CachedResultFetcher $cachedResultFetcher,
        protected readonly CachedDbTranslationRepository $cachedDbTranslationRepository,
    )
    {
    }

    public function getRespondentMailCodes(): array
    {
        $select = $this->cachedResultFetcher->getResultFetcher()->getSelect('gems__mail_codes');
        $select->columns(['gmc_id', 'gmc_mail_to_target'])
            ->where([
                'gmc_for_respondents' => 1,
                'gmc_active' => 1,
            ]);

        $result = $this->cachedResultFetcher->fetchAll('respondentMailCodes', $select, null, ['mailcodes']);

        $translatedResult = $this->cachedDbTranslationRepository->translateTable('respondentMailCodes', 'gems__mail_codes', 'gmc_id', $result);

        $pairs = array_column($translatedResult, 'gmc_mail_to_target', 'gmc_id');
        ksort($pairs);
        return $pairs;
    }

    public function getRespondentTrackMailCodes(): array
    {
        $select = $this->cachedResultFetcher->getResultFetcher()->getSelect('gems__mail_codes');
        $select->columns(['gmc_id', 'gmc_mail_to_target'])
            ->where([
               'gmc_for_tracks' => 1,
               'gmc_active' => 1,
            ]);

        $result = $this->cachedResultFetcher->fetchAll('respondentTrackMailCodes', $select, null, ['mailcodes'],);
        $translatedResult = $this->cachedDbTranslationRepository->translateTable('respondentMailCodes', 'gems__mail_codes', 'gmc_id', $result);

        $pairs = array_column($translatedResult, 'gmc_mail_to_target', 'gmc_id');
        ksort($pairs);
        return $pairs;
    }
    public function getRespondentTrackNoMailCodeValue(): int
    {
        $mailCodes = $this->getRespondentTrackMailCodes();
        reset($mailCodes);
        return (int)key($mailCodes);
    }

    public function getRespondentNoMailCodeValue()
    {
        $mailCodes = $this->getRespondentMailCodes();
        reset($mailCodes);
        return key($mailCodes);
    }

    public function getMailTargets(): array
    {
        return [
            'staff' => 'Staff',
            'respondent' => 'Respondent',
            'token' => 'Token',
            'staffPassword' => 'Password reset',
        ];
    }
}