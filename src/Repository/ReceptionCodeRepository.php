<?php

namespace Gems\Repository;

use Gems\Db\CachedResultFetcher;
use Gems\Db\ResultFetcher;
use Gems\Exception;
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
    public const RESPONDENT_TYPE_FIELD = 'grc_for_respondents';
    public const SURVEY_TYPE_FIELD = 'grc_for_surveys';
    public const TRACK_TYPE_FIELD = 'grc_for_tracks';
    public const TYPES = [
        'grc_for_respondents' => ReceptionCode::TYPE_RESPONDENT,
        'grc_for_surveys' => ReceptionCode::TYPE_SURVEY,
        'grc_for_tracks' => ReceptionCode::TYPE_TRACK,
    ];

    protected array $cacheTags = ['receptionCode', 'receptionCodes'];

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

        $filteredCodes = array_filter($allReceptionCodes, function ($row) {
            return $row[self::SURVEY_TYPE_FIELD] != 0 && $row[self::SUCCESS_FIELD] == 0;
        });

        return array_column($filteredCodes, 'grc_description', 'grc_id_reception_code');
    }

    protected function getReceptionCodeFromData(array $data): ReceptionCode
    {
        $description = null;
        if (isset($data['grc_description'])) {
            $description = $this->translator->_($data['grc_description']);
        }

        return new ReceptionCode(
            $data['grc_id_reception_code'],
            $this->deduceReceptionCodeTypesFromData($data),
            (bool)$data['grc_success'],
            $description,
            (bool)$data['grc_redo_survey'],
            $data['grc_redo_survey'] === 2,
            $data['grc_for_surveys'] === 2,
            (bool)$data['grc_overwrite_answers'],
        );
    }

    /**
     * Construct a bitmask value that describes all the types that this
     * reception code can be used for.
     *
     * @param array $data Database row
     * @return integer
     */
    private function deduceReceptionCodeTypesFromData(array $data): int
    {
        $types = 0;
        foreach (self::TYPES as $property => $typeValue) {
            if (!isset($data[$property])) {
                continue;
            }
            if ($data[$property] <= 0) {
                continue;
            }
            $types = $types | $typeValue;
        }

        return $types;
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
        return $this->cachedResultFetcher->fetchAll(static::class . 'allReceptionCodes', $select, null, $this->cacheTags);
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

        $filteredCodes = array_filter($allReceptionCodes, function ($row) {
            return $row[self::RESPONDENT_TYPE_FIELD] != 0 && $row[self::SUCCESS_FIELD] == 0 && $row[self::REDO_FIELD] == 0;
        });

        return array_column($filteredCodes, 'grc_description', 'grc_id_reception_code');
    }

    /**
     * Returns the respondent deletion reception code list.
     *
     * @return array a value => label array.
     */
    public function getRespondentRestoreCodes(): array
    {
        $allReceptionCodes = $this->getAllActiveReceptionCodes();

        $filteredCodes = array_filter($allReceptionCodes, function ($row) {
            return $row[self::RESPONDENT_TYPE_FIELD] != 0 && $row[self::SUCCESS_FIELD] == 1;
        });

        return array_column($filteredCodes, 'grc_description', 'grc_id_reception_code');
    }

    public function getSuccessCodesFor(string $field): array
    {
        $allReceptionCodes = $this->getAllActiveReceptionCodes();

        $successCodes = array_filter($allReceptionCodes, function ($row) use ($field) {
            return $row[$field] != 0 && $row[self::SUCCESS_FIELD] == 1;
        });

        return array_column($successCodes, 'grc_description', 'grc_id_reception_code');
    }

    public function getSuccessCodesForRespondent(): array
    {
        return $this->getSuccessCodesFor(self::RESPONDENT_TYPE_FIELD);
    }

    public function getSuccessCodesForSurvey(): array
    {
        return $this->getSuccessCodesFor(self::SURVEY_TYPE_FIELD);
    }

    public function getSuccessCodesForTrack(): array
    {
        return $this->getSuccessCodesFor(self::TRACK_TYPE_FIELD);
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
            return $row[self::SUCCESS_FIELD] == 0 && ($row[self::TRACK_TYPE_FIELD] == 1 || $row[self::RESPONDENT_TYPE_FIELD] == 2);
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
            return $row[self::SUCCESS_FIELD] == 1 && ($row[self::TRACK_TYPE_FIELD] == 1 || $row[self::RESPONDENT_TYPE_FIELD] == 2);
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

        $filteredCodes = array_filter($allReceptionCodes, function ($row) {
            return $row[self::SURVEY_TYPE_FIELD] == 0 && $row[self::SUCCESS_FIELD] == 0 && $row[self::REDO_FIELD] == 0;
        });

        return array_column($filteredCodes, 'grc_description', 'grc_id_reception_code');
    }

    /**
     * Returns the token restore reception code list.
     *
     * @return array a value => label array.
     */
    public function getTokenRestoreCodes(): array
    {
        $allReceptionCodes = $this->getAllActiveReceptionCodes();

        $successCodes = array_filter($allReceptionCodes, function ($row) {
            return $row[self::SUCCESS_FIELD] == 1 && $row[self::SURVEY_TYPE_FIELD] == 1;
        });

        return array_column($successCodes, 'grc_description', 'grc_id_reception_code');
    }
}
