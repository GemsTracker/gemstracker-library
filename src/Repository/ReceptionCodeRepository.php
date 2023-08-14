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

    const RECEPTION_OK = 'OK';
    const RECEPTION_SKIP = 'skip';
    const RECEPTION_STOP = 'stop';
    public const ACTIVE_FIELD = 'grc_active';
    public const REDO_FIELD = 'grc_redo_survey';
    public const SUCCESS_FIELD = 'grc_success';

    public function __construct(
        protected CachedResultFetcher $cachedResultFetcher,
        protected Translator $translator,
    ) {
    }

    /**
     * Returns the token deletion reception code list.
     *
     * @return array a value => label array.
     */
    public function getCompletedTokenDeletionCodes(): array
    {
        $allReceptionCodes = $this->getAllActiveReceptionCodes();

        $filteredCode = array_filter($allReceptionCodes, function ($row) {
            return $row[ReceptionCodeType::SURVEY->getDatabaseField()] != 0 && $row[self::SUCCESS_FIELD] == 0;
        });

        return array_column($filteredCode, 'grc_description', 'grc_id_reception_code');
    }

    protected function getReceptionCodeFromData(array $data): ReceptionCode
    {
        $description = null;
        if (isset($data['grc_description'])) {
            $description = $this->translator->_($data['grc_description']);
        }

        return new ReceptionCode(
            $data['grc_id_reception_code'],
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

    public function getAllActiveReceptionCodes(): array
    {
        $allReceptionCodes = $this->getAllReceptionCodes();
        return array_filter($allReceptionCodes, function ($row) {
            return $row[self::ACTIVE_FIELD] == 1;
        });
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
            if ($receptionCode['grc_id_reception_code'] === $code) {
                return $this->getReceptionCodeFromData($receptionCode);
            }
        }
        throw new Exception(sprintf('Reception code %s not found.', $code));
    }

    /**
     * Returns the respondent deletion reception code list.
     *
     * @return array a value => label array.
     */
    public function getRespondentDeletionCodes(): array
    {
        $allReceptionCodes = $this->getAllActiveReceptionCodes();

        $filteredCode = array_filter($allReceptionCodes, function ($row) {
            return $row[ReceptionCodeType::RESPONDENT->getDatabaseField()] != 0 && $row[self::SUCCESS_FIELD] == 0 && $row[self::REDO_FIELD] == 0;
        });

        return array_column($filteredCode, 'grc_description', 'grc_id_reception_code');
    }

    /**
     * Returns the respondent deletion reception code list.
     *
     * @return array a value => label array.
     */
    public function getRespondentRestoreCodes(): array
    {
        $allReceptionCodes = $this->getAllActiveReceptionCodes();

        $filteredCode = array_filter($allReceptionCodes, function ($row) {
            return $row[ReceptionCodeType::RESPONDENT->getDatabaseField()] != 0 && $row[self::SUCCESS_FIELD] == 1;
        });

        return array_column($filteredCode, 'grc_description', 'grc_id_reception_code');
    }

    public function getSuccessCodesFor(ReceptionCodeType $type): array
    {
        $allReceptionCodes = $this->getAllActiveReceptionCodes();

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

    /**
     * Returns the track deletion reception code list.
     *
     * @return array a value => label array.
     */
    public function getTrackDeletionCodes(): array
    {
        $allReceptionCodes = $this->getAllActiveReceptionCodes();

        $successCodes = array_filter($allReceptionCodes, function ($row) {
            return $row[self::SUCCESS_FIELD] == 0 && ($row[ReceptionCodeType::TRACK->getDatabaseField()] == 1 || $row[ReceptionCodeType::RESPONDENT->getDatabaseField()] == 2);
        });

        return array_column($successCodes, 'grc_description', 'grc_id_reception_code');
    }

    /**
     * Returns the track restore reception code list.
     *
     * @return array a value => label array.
     */
    public function getTrackRestoreCodes(): array
    {
        $allReceptionCodes = $this->getAllActiveReceptionCodes();

        $successCodes = array_filter($allReceptionCodes, function ($row) {
            return $row[self::SUCCESS_FIELD] == 1 && ($row[ReceptionCodeType::TRACK->getDatabaseField()] == 1 || $row[ReceptionCodeType::RESPONDENT->getDatabaseField()] == 2);
        });

        return array_column($successCodes, 'grc_description', 'grc_id_reception_code');
    }

    /**
     * Returns the token deletion reception code list.
     *
     * @return array a value => label array.
     */
    public function getUnansweredTokenDeletionCodes(): array
    {
        $allReceptionCodes = $this->getAllActiveReceptionCodes();

        $filteredCode = array_filter($allReceptionCodes, function ($row) {
            return $row[ReceptionCodeType::SURVEY->getDatabaseField()] == 0 && $row[self::SUCCESS_FIELD] == 0 && $row[self::REDO_FIELD] == 0;
        });

        return array_column($filteredCode, 'grc_description', 'grc_id_reception_code');
    }
}