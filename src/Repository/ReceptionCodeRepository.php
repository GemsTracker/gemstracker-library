<?php

namespace Gems\Repository;

use Gems\Db\CachedResultFetcher;
use Gems\Db\ResultFetcher;
use Gems\Exception;
use Gems\ReceptionCode\ReceptionCodeType;
use Gems\Tracker\ReceptionCode;
use MUtil\Translate\Translator;

class ReceptionCodeRepository
{
    public const SUCCESS_FIELD = 'grc_success';

    public function __construct(
        protected CachedResultFetcher $cachedResultFetcher,
        protected Translator $translator,
    ) {
    }

    protected function getReceptionCodeFromData(array $data): ReceptionCode
    {
        $description = null;
        if (isset($data['grc_description'])) {
            $description = $this->translator->_($data['grc_description']);
        }

        return new ReceptionCode(
            $data['grc_id_reception_code '],
            ReceptionCodeType::createFromData($data),
            (bool)$data['grc_success'],
            $description,
            (bool)$data['grc_redo_survey'],
            $data['grc_redo_survey'] === 2,
            $data['grc_for_surveys'] === 2,
            (bool)$data['grc_overwrite_answers'],
        );
    }

    /**
     * @param array $dataList
     * @return ReceptionCode[]
     */
    protected function getReceptionCodesFromData(array $dataList): array
    {
        $receptionCodes = [];
        foreach($dataList as $receptionCodeData) {
            if (!$receptionCodeData['grc_active']) {
                continue;
            }
            $receptionCodes[] = $this->getReceptionCodeFromData($receptionCodeData);
        }
        return $receptionCodes;
    }

    public function getAllReceptionCodes(): array
    {
        $select = $this->cachedResultFetcher->getSelect('gems__reception_codes');
        return $this->cachedResultFetcher->fetchAll(static::class . 'allReceptionCodes', $select);
    }

    public function getReceptionCode(string $code): ReceptionCode
    {
        $allReceptionCodes = $this->getAllReceptionCodes();
        foreach($allReceptionCodes as $receptionCode) {
            if ($receptionCode['grc_code'] === $code) {
                return $this->getReceptionCodeFromData($receptionCode);
            }
        }
        throw new Exception(sprintf('Reception code %s not found.', $code));
    }

    public function getSuccessCodesFor(ReceptionCodeType $type): array
    {
        $allReceptionCodes = $this->getAllReceptionCodes();

        $successCodes = array_filter($allReceptionCodes, function ($row) use ($type) {
            return $row[$type->getDatabaseField()] != 0 && $row[self::SUCCESS_FIELD] == 1;
        });

        return array_column($successCodes, 'grc_description', 'grc_id_reception_code');
    }

    public function getSuccessCodesForRespondent(): array
    {
        return $this->getSuccessCodesFor(ReceptionCodeType::RESPONDENT);
    }

    public function getSuccessCodesForSurvey(): array
    {
        return $this->getSuccessCodesFor(ReceptionCodeType::SURVEY);
    }

    public function getSuccessCodesForTrack(): array
    {
        return $this->getSuccessCodesFor(ReceptionCodeType::TRACK);
    }
}