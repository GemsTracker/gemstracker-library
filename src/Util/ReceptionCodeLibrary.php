<?php

/**
 *
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Util;

use Gems\Db\ResultFetcher;
use Gems\Locale\Locale;
use Gems\Project\ProjectSettings;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use Zalt\Base\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;

/**
 * Library functions and constants for working with reception codes.
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class ReceptionCodeLibrary
{
    const APPLY_NOT  = 0;
    const APPLY_DO   = 1;
    const APPLY_STOP = 2;

    const RECEPTION_OK = 'OK';

    const REDO_NONE = 0;
    const REDO_ONLY = 1;
    const REDO_COPY = 2;

    /**
     * @var string Language name or null if we DON'T USE the language
     */
    protected ?string $language = null;

    protected ProjectOverloader $utilOverloader;

    public function __construct(
        protected ResultFetcher $resultFetcher,
        protected TranslatorInterface $translator,
        ProjectSettings $projectSettings,
        Locale $locale,
        ProjectOverloader $overloader,
    ) {
        $language = $locale->getLanguage();

        if ($projectSettings->translateDatabaseFields() && ($language != $projectSettings->getLocaleDefault())) {
            $this->language = $language;
        }

        $this->utilOverloader = $overloader->createSubFolderOverloader('Util');
    }
    
    /**
     *
     * @return Select for a fetchPairs
     */
    protected function _getDeletionCodeSelect(): Select
    {
        $select = $this->resultFetcher->getSelect('gems__reception_codes');
        $select->columns(['grc_id_reception_code', 'grc_description'])
            ->where([
                'grc_success' => 0,
                'grc_active' => 1
            ])
            ->order(['grc_description']);

        return $select;
    }

    /**
     *
     * @return Select for a fetchPairs
     */
    protected function _getRestoreSelect(): Select
    {
        $select = $this->resultFetcher->getSelect('gems__reception_codes');
        $select->columns([
            'grc_id_reception_code',
            'name' => new Expression(
                "CASE
                        WHEN grc_description IS NULL OR grc_description = '' THEN grc_id_reception_code
                        ELSE grc_description
                        END")
        ])
            ->where([
                'grc_success' => 1,
                'grc_active' => 1,
            ])
            ->order(['grc_description']);

        return $select;
    }

    /**
     * Translate and sort while maintaining key association
     *
     * @param array $pairs
     * @return array
     */
    protected function _translateAndSort(array $pairs): array
    {
        static $translations;

        if ($this->language) {
            if (! $translations) {
                $tSelect = $this->resultFetcher->getSelect('gems__translations');
                $tSelect->columns(['gtrs_keys', 'gtrs_translation'])
                    ->where([
                        'gtrs_table' => 'gems__reception_codes',
                        'gtrs_field' => 'grc_description',
                        'gtrs_iso_lang' => $this->language,
                    ])->where->greaterThan(new \Laminas\Db\Sql\Predicate\Expression('LENGTH(gtrs_translation)'), 0);

                $translations = $this->resultFetcher->fetchPairs($tSelect);
            }
        
            foreach ($pairs as $code => $description) {
                if (isset($translations[$code])) {
                    $pairs[$code] = $translations[$code];
                }
            }
        }

        asort($pairs);

        return $pairs;
    }

    /**
     * Returns the token deletion reception code list.
     *
     * @return array a value => label array.
     */
    public function getCompletedTokenDeletionCodes(): array
    {
        $select = $this->_getDeletionCodeSelect();
        $select->where(['grc_for_surveys', self::APPLY_DO]);

        return $this->_translateAndSort($this->resultFetcher->fetchPairs($select));
    }

    /**
     * Returns the string version of the OK code
     *
     * @return string
     */
    public function getOKString(): string
    {
        return static::RECEPTION_OK;
    }

    /**
     * Returns a single reception code object.
     *
     * @param string $code
     * @return \Gems\Util\ReceptionCode
     */
    public function getReceptionCode($code)
    {
        static $codes = array();

        if (! isset($codes[$code])) {
            $codes[$code] = $this->utilOverloader->create('ReceptionCode', $code);
        }

        return $codes[$code];
    }


    /**
     * Return the field values for the redo code.
     *
     * <ul><li>0: do not redo</li>
     * <li>1: redo but do not copy answers</li>
     * <li>2: redo and copy answers</li></ul>
     *
     * @staticvar array $data
     * @return array
     */
    public function getRedoValues(): array
    {
        static $data;

        if (! $data) {
            $data = array(
                self::REDO_NONE => $this->translator->_('No'),
                self::REDO_ONLY => $this->translator->_('Yes (forget answers)'),
                self::REDO_COPY => $this->translator->_('Yes (keep answers)'));
        }

        return $data;
    }

    /**
     * Returns the string version of the skip code
     *
     * @return string
     */
    public function getSkipString(): string
    {
        return 'skip';
    }
    

    /**
     * Returns the string version of the Stop code
     *
     * @return string
     */
    public function getStopString(): string
    {
        return 'stop';
    }

    /**
     * Return the field values for surveys.
     *
     * <ul><li>0: do not use</li>
     * <li>1: use (and cascade)</li>
     * <li>2: use for open rounds only</li></ul>
     *
     * @staticvar array $data
     * @return array
     */
    public function getSurveyApplicationValues(): array
    {
        static $data;

        if (! $data) {
            $data = array(
                self::APPLY_NOT  => $this->translator->_('No'),
                self::APPLY_DO   => $this->translator->_('Yes (for individual tokens)'),
                self::APPLY_STOP => $this->translator->_('Stop (for tokens in uncompleted tracks)'));
        }

        return $data;
    }

    /**
     * Returns the respondent deletion reception code list.
     *
     * @return array a value => label array.
     */
    public function getRespondentDeletionCodes(): array
    {
        $select = $this->_getDeletionCodeSelect();
        $select->where(['grc_for_respondents' => 1]);

        return $this->_translateAndSort($this->resultFetcher->fetchPairs($select));
    }

    /**
     * Returns the respondent deletion reception code list.
     *
     * @return array a value => label array.
     */
    public function getRespondentRestoreCodes(): array
    {
        $select = $this->_getRestoreSelect();
        $select->where(['grc_for_respondents' => 1]);

        return $this->_translateAndSort($this->resultFetcher->fetchPairs($select));
    }

    /**
     * Returns the token deletion reception code list.
     *
     * @return array a value => label array.
     */
    public function getTokenRestoreCodes(): array
    {
        $select = $this->_getRestoreSelect();
        $select->where(['grc_for_surveys' => self::APPLY_DO]);

        return $this->_translateAndSort($this->resultFetcher->fetchPairs($select));
    }

    /**
     * Returns the track deletion reception code list.
     *
     * @return array a value => label array.
     */
    public function getTrackDeletionCodes(): array
    {
        $select = $this->_getDeletionCodeSelect();
        $select->where->nest()->equalTo('grc_for_tracks', 1)->or->equalTo('grc_for_surveys', self::APPLY_STOP);

        return $this->_translateAndSort($this->resultFetcher->fetchPairs($select));
    }

    /**
     * Returns the track deletion reception code list.
     *
     * @return array a value => label array.
     */
    public function getTrackRestoreCodes(): array
    {
        $select = $this->_getRestoreSelect();
        $select->where->nest()->equalTo('grc_for_tracks', 1)->or->equalTo('grc_for_surveys', self::APPLY_STOP);

        return $this->_translateAndSort($this->resultFetcher->fetchPairs($select));
    }

    /**
     * Returns the token deletion reception code list.
     *
     * @return array a value => label array.
     */
    public function getUnansweredTokenDeletionCodes(): array
    {
        $select = $this->_getDeletionCodeSelect();
        $select->where([
            'grc_for_surveys' => self::APPLY_DO,
            'grc_redo_survey' => self::REDO_NONE,
        ]);

        return $this->_translateAndSort($this->resultFetcher->fetchPairs($select));
    }
}
