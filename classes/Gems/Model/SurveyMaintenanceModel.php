<?php

/**
 *
 * @package    Gems
 * @subpackage Model
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2019 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Model;

/**
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.7
 */
class SurveyMaintenanceModel extends \Gems_Model_JoinModel {
    
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;
    
    /**
     *
     * @var \Gems_Loader
     */
    public $loader;
    
    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    public $project;
    
    /**
     *
     * @var \Gems_Util
     */
    public $util;
    
    /**
     *
     * @param string $name
     */
    public function __construct($name = 'surveymaintenance')
    {
        parent::__construct($name, 'gems__surveys', 'gsu');
        $this->addTable('gems__sources', array('gsu_id_source' => 'gso_id_source'));
        $this->setCreate(false);
    }

    /**
     * Set those settings needed for the browse display
     *
     * @param boolean $addCount Add a count in rounds column
     * @return \Gems\Model\ConditionModel
     */
    public function applyBrowseSettings($addCount = true)
    {
        $dbLookup   = $this->util->getDbLookup();
        $survey     = null;
        $translated = $this->util->getTranslated();
        $yesNo      = $translated->getYesNo();
        
        $this->addColumn(
                "CASE WHEN gsu_survey_pdf IS NULL OR CHAR_LENGTH(gsu_survey_pdf) = 0 THEN 0 ELSE 1 END",
                'gsu_has_pdf'
                );
        $this->addColumn(
                sprintf(
                        "CASE WHEN (gsu_status IS NULL OR gsu_status = '') THEN '%s' ELSE gsu_status END",
                        $this->_('OK')
                        ),
                'gsu_status_show',
                'gsu_status'
                );
        $this->addColumn(
                "CASE WHEN gsu_surveyor_active THEN '' ELSE 'deleted' END",
                'row_class'
                );

        $this->resetOrder();

        $this->set('gsu_survey_name',        'label', $this->_('Name'),
                'elementClass', 'Exhibitor'
                );
        $this->set('gsu_survey_description', 'label', $this->_('Description'),
                'elementClass', 'Exhibitor',
                'formatFunction', array($this, 'formatDescription')
                );
        
        $this->set('gsu_survey_languages',        'label', $this->_('Available languages'),
                'elementClass', 'Exhibitor',
                'itemDisplay', array($this, 'formatLanguages'));
        
        $this->set('gsu_survey_warnings',        'label', $this->_('Warnings'),
                'elementClass', 'Exhibitor'
                );
        $this->set('gso_source_name',        'label', $this->_('Source'),
                'elementClass', 'Exhibitor');
        $this->set('gsu_surveyor_active',    'label', $this->_('Active in source'),
                'elementClass', 'Exhibitor',
                'multiOptions', $yesNo
                );
        $this->set('gsu_surveyor_id',    'label', $this->_('Source survey id'),
                'elementClass', 'Exhibitor'
                );
        $this->set('gsu_status_show',        'label', $this->_('Status in source'),
                'elementClass', 'Exhibitor');
        $this->set('gsu_active',             'label', sprintf($this->_('Active in %s'), $this->project->getName()),
                'elementClass', 'Checkbox',
                'multiOptions', $yesNo
                );
        $this->set('gsu_id_primary_group',   'label', $this->_('Group'),
                'description', $this->_('If empty, survey will never show up!'),
                'multiOptions', $dbLookup->getGroups()
                );        

        $this->set('gsu_insertable',         'label', $this->_('Insertable'),
                'description', $this->_('Can this survey be manually inserted into a track?'),
                'elementClass', 'Checkbox',
                'multiOptions', $yesNo,
                'onclick', 'this.form.submit()'
                );
        
        if ($addCount) {
            $this->set('track_count',          'label', ' ',
                    'elementClass', 'Exhibitor',
                    'noSort', true,
                    'no_text_search', true
                    );
            $this->setOnLoad('track_count', array($this, 'calculateTrackCount'));
        }
        
        $this->set('gsu_code',                 'label', $this->_('Survey code'),
                'description', $this->_('Optional code name to link the survey to program code.'),
                'size', 10);

        $this->set('gsu_export_code',               'label', $this->_('Survey export code'),
                'description', $this->_('A unique code indentifying this survey during track import'),
                'size', 20);

        return $this;
    }

    /**
     * Set those settings needed for the detailed display
     *
     * @return \Gems\Model\ConditionModel
     */
    public function applyDetailSettings($surveyId = null)
    {
        $this->applyBrowseSettings(false);
        $translated = $this->util->getTranslated();
        $dbLookup   = $this->util->getDbLookup();
        
        $this->resetOrder();
        
        $this->setMulti([
            'gsu_survey_name',
            'gsu_survey_description',
            'gsu_survey_languages',
            'gsu_survey_warnings',
            'gso_source_name',
            'gsu_surveyor_active',
            'gsu_surveyor_id',
            'gsu_status_show',
            'gsu_active',
            'gsu_id_primary_group',
            'gsu_insertable'            
            ]);
                
        $this->addDependency('CanEditDependency', 'gsu_surveyor_active', array('gsu_active'));
        $this->set('gsu_active',
                'validators[group]', new \MUtil_Validate_Require(
                        $this->get('gsu_active', 'label'),
                        'gsu_id_primary_group',
                        $this->get('gsu_id_primary_group', 'label')
                        ));
                
        $this->set('gsu_valid_for_length', 'label', $this->_('Add to inserted end date'),
                'description', $this->_('Add to the start date to calculate the end date when inserting.'),
                'filter', 'Int'
                );
        $this->set('gsu_valid_for_unit',   'label', $this->_('Inserted end date unit'),
                'description', $this->_('The unit used to calculate the end date when inserting the survey.'),
                'multiOptions', $translated->getPeriodUnits()
                );
        $this->set('gsu_insert_organizations', 'label', $this->_('Insert organizations'),
                'description', $this->_('The organizations where the survey may be inserted.'),
                'elementClass', 'MultiCheckbox',
                'multiOptions', $dbLookup->getOrganizations(),
                'required', true
                );
        $ct = new \MUtil_Model_Type_ConcatenatedRow('|', $this->_(', '));
        $ct->apply($this, 'gsu_insert_organizations');

        $switches = array(
            0 => array(
                'gsu_valid_for_length'     => array('elementClass' => 'Hidden', 'label' => null),
                'gsu_valid_for_unit'       => array('elementClass' => 'Hidden', 'label' => null),
                'gsu_insert_organizations' => array('elementClass' => 'Hidden', 'label' => null),
                'toggleOrg'                => array('elementClass' => 'Hidden', 'label' => null),
            ),
        );
        $this->addDependency(array('ValueSwitchDependency', $switches), 'gsu_insertable');
        
        $this->set('track_usage',          'label', $this->_('Usage'),
                'elementClass', 'Exhibitor',
                'noSort', true,
                'no_text_search', true
                );
        $this->setOnLoad('track_usage', array($this, 'calculateTrackUsage'));

        $this->set('calc_duration',        'label', $this->_('Duration calculated'),
                'elementClass', 'Html',
                'noSort', true,
                'no_text_search', true
                );
        $this->setOnLoad('calc_duration', array($this, 'calculateDuration'));

        $this->set('gsu_duration',         'label', $this->_('Duration description'),
                'description', $this->_('Text to inform the respondent, e.g. "20 seconds" or "1 minute".')
                );
        
        if ($surveyId) {
            $survey = $this->loader->getTracker()->getSurvey($surveyId);
        }
        
        if ($survey instanceof \Gems_Tracker_Survey) {
            $surveyFields = $this->util->getTranslated()->getEmptyDropdownArray() +
                $survey->getQuestionList(null);
            $this->set('gsu_result_field', 'label', $this->_('Result field'),
                    'multiOptions', $surveyFields
                    );
            // $model->set('gsu_agenda_result',         'label', $this->_('Agenda field'));
        }
        
        $this->set('gsu_code');
        $this->set('gsu_export_code');
        
        $events = $this->loader->getEvents();
        $beforeOptions = $events->listSurveyBeforeAnsweringEvents();
        if (count($beforeOptions) > 1) {
            $this->set('gsu_beforeanswering_event', 'label', $this->_('Before answering'),
                    'multiOptions', $beforeOptions,
                    'elementClass', 'Select'
                    );
        }
        $completedOptions = $events->listSurveyCompletionEvents();
        if (count($completedOptions) > 1) {
            $this->set('gsu_completed_event',       'label', $this->_('After completion'),
                    'multiOptions', $completedOptions,
                    'elementClass', 'Select'
                    );
        }
        $displayOptions = $events->listSurveyDisplayEvents();
        if (count($displayOptions) > 1) {
            $this->set('gsu_display_event',         'label', $this->_('Answer display'),
                    'multiOptions', $displayOptions,
                    'elementClass', 'Select'
                    );
        }

        return $this;
    }

    /**
     * Set those values needed for editing
     *
     * @return \Gems\Model\ConditionModel
     */
    public function applyEditSettings($create = false, $surveyId = null)
    {
        $this->applyDetailSettings($surveyId);
        
        $order = $this->getOrder('gsu_insert_organizations') + 1;
        
        $this->set('toggleOrg',
                    'elementClass', 'ToggleCheckboxes',
                    'selectorName', 'gsu_insert_organizations',
                    'order', $order
                    );
        
        $this->set('gsu_survey_pdf', 'label', 'Pdf',	
                        'accept', 'application/pdf',	
                        'destination', $this->loader->getPdf()->getUploadDir('survey_pdfs'),	
                        'elementClass', 'File',	
                        'extension', 'pdf',	
                        'filename', $surveyId,	
                        'required', false,	
                        'validators[pdf]', new \MUtil_Validate_Pdf()	
                        );

        return $this;
    }
    
    /**
     * A ModelAbstract->setOnLoad() function that takes care of transforming a
     * dateformat read from the database to a \Zend_Date format
     *
     * If empty or \Zend_Db_Expression (after save) it will return just the value
     * currently there are no checks for a valid date format.
     *
     * @see \MUtil_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return \MUtil_Date|\Zend_Db_Expr|string
     */
    public function calculateDuration($value, $isNew = false, $name = null, array $context = array(), $isPost = false)
    {
        $surveyId = isset($context['gsu_id_survey']) ? $context['gsu_id_survey'] : false;
        if (! $surveyId) {
            return $this->_('incalculable');
        }

        $fields['cnt'] = 'COUNT(DISTINCT gto_id_token)';
        $fields['avg'] = 'AVG(CASE WHEN gto_duration_in_sec > 0 THEN gto_duration_in_sec ELSE NULL END)';
        $fields['std'] = 'STDDEV_POP(CASE WHEN gto_duration_in_sec > 0 THEN gto_duration_in_sec ELSE NULL END)';

        $select = $this->loader->getTracker()->getTokenSelect($fields);
        $select->forSurveyId($surveyId)
                ->onlyCompleted();

        $row = $select->fetchRow();
        if ($row) {
            $trs = $this->util->getTranslated();
            $seq = new \MUtil_Html_Sequence();
            $seq->setGlue(\MUtil_Html::create('br'));

            $seq->sprintf($this->_('Answered surveys: %d.'), $row['cnt']);
            $seq->sprintf(
                    $this->_('Average answer time: %s.'),
                    $row['cnt'] ? $trs->formatTimeUnknown($row['avg']) : $this->_('n/a')
                    );
            $seq->sprintf(
                    $this->_('Standard deviation: %s.'),
                    $row['cnt'] ? $trs->formatTimeUnknown($row['std']) : $this->_('n/a')
                    );

            if ($row['cnt']) {
                // Picked solution from http://stackoverflow.com/questions/1291152/simple-way-to-calculate-median-with-mysql
                $sql = "SELECT t1.gto_duration_in_sec as median_val
                            FROM (SELECT @rownum:=@rownum+1 as `row_number`, gto_duration_in_sec
                                    FROM gems__tokens, (SELECT @rownum:=0) r
                                    WHERE gto_id_survey = ? AND gto_completion_time IS NOT NULL
                                    ORDER BY gto_duration_in_sec
                                ) AS t1,
                                (SELECT count(*) as total_rows
                                    FROM gems__tokens
                                    WHERE gto_id_survey = ? AND gto_completion_time IS NOT NULL
                                ) as t2
                            WHERE t1.row_number = floor(total_rows / 2) + 1";
                $med = $this->db->fetchOne($sql, array($surveyId, $surveyId));
                if ($med) {
                    $seq->sprintf($this->_('Median value: %s.'), $trs->formatTimeUnknown($med));
                }
                // \MUtil_Echo::track($row, $med, $sql, $select->getSelect()->__toString());
            } else {
                $seq->append(sprintf($this->_('Median value: %s.'), $this->_('n/a')));
            }

            return $seq;
        }
    }

    /**
     * A ModelAbstract->setOnLoad() function that takes care of transforming a
     * dateformat read from the database to a \Zend_Date format
     *
     * If empty or \Zend_Db_Expression (after save) it will return just the value
     * currently there are no checks for a valid date format.
     *
     * @see \MUtil_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return \MUtil_Date|\Zend_Db_Expr|string
     */
    public function calculateTrackCount($value, $isNew = false, $name = null, array $context = array(), $isPost = false)
    {
        $surveyId = isset($context['gsu_id_survey']) ? $context['gsu_id_survey'] : false;
        if (! $surveyId) {
            return 0;
        }

        $select = new \Zend_Db_Select($this->db);
        $select->from('gems__rounds', array('useCnt' => 'COUNT(*)', 'trackCnt' => 'COUNT(DISTINCT gro_id_track)'));
        $select->joinLeft('gems__tracks', 'gtr_id_track = gro_id_track', array())
                ->where('gro_id_survey = ?', $surveyId);
        $counts = $select->query()->fetchObject();

        if ($counts && ($counts->useCnt || $counts->trackCnt)) {
            return sprintf($this->_('%d times in %d track(s).'), $counts->useCnt, $counts->trackCnt);
        } else {
            return $this->_('Not in any track.');
        }
    }

    /**
     * A ModelAbstract->setOnLoad() function that takes care of transforming a
     * dateformat read from the database to a \Zend_Date format
     *
     * If empty or \Zend_Db_Expression (after save) it will return just the value
     * currently there are no checks for a valid date format.
     *
     * @see \MUtil_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return \MUtil_Date|\Zend_Db_Expr|string
     */
    public function calculateTrackUsage($value, $isNew = false, $name = null, array $context = array(), $isPost = false)
    {
        $surveyId = isset($context['gsu_id_survey']) ? $context['gsu_id_survey'] : false;
        if (! $surveyId) {
            return 0;
        }

        $select = new \Zend_Db_Select($this->db);
        $select->from('gems__tracks', array('gtr_track_name'));
        $select->joinLeft('gems__rounds', 'gro_id_track = gtr_id_track', array('useCnt' => 'COUNT(*)'))
                ->where('gro_id_survey = ?', $surveyId)
                ->group('gtr_track_name');
        $usage = $this->db->fetchPairs($select);

        if ($usage) {
            $seq = new \MUtil_Html_Sequence();
            $seq->setGlue(\MUtil_Html::create('br'));
            foreach ($usage as $track => $count) {
                $seq[] = sprintf($this->plural(
                        '%d time in %s track.',
                        '%d times in %s track.',
                        $count), $count, $track);
            }
            return $seq;

        } else {
            return $this->_('Not in any track.');
        }
    }

    
    /**
     * Strip all the tags, but keep the escaped characters
     *
     * @param string $value
     * @return \MUtil_Html_Raw
     */
    public static function formatDescription($value)
    {
        return \MUtil_Html::raw(strip_tags($value));
    }
    
    /**
     * Divide available languages between base and additional languages and format output
     *
     * @param string $value
     * @param boolean $native Return native translation
     * @return string $seq
     */
    public function formatLanguages($value, $native = false)
    {
        $split = explode(', ', $value);
        
        foreach ($split as $key => $locale) {
            $localized = '';
            if (\Zend_Locale::isLocale($locale, false)) {
                if ($native) {
                    $localized = \Zend_Locale::getTranslation($locale, 'Language', $locale);
                } else {
                    $localized = \Zend_Locale::getTranslation($locale, 'Language');
                }
            }
            $split[$key] = $localized ? $localized : $locale;
        }
        
        $seq = new \MUtil_Html_Sequence();
        $seq->setGlue(\MUtil_Html::create('br'));

        $seq->append(sprintf($this->_('Base: %s'), $split[0]));
        if (isset($split[1])) {
            $seq->append(sprintf($this->_('Additional: %s'), implode(', ', array_slice($split, 1))));
        }
        
        return $seq;
    }
    
    /**
     * Return array with language labels
     * Simplified array from getLanguageData() in surveytranslator_helper.php
     *
     * @return array $supportedLanguages
     */
    public function getLanguageDescription()
    {
        // Afrikaans
        $supportedLanguages['af']['description'] = $this->_('Afrikaans');
        $supportedLanguages['af']['nativedescription'] = 'Afrikaans';

        // Albanian
        $supportedLanguages['sq']['description'] = $this->_('Albanian');
        $supportedLanguages['sq']['nativedescription'] = 'Shqipe';

        // Amharic
        $supportedLanguages['am']['description'] = $this->_('Amharic');
        $supportedLanguages['am']['nativedescription'] = '&#4768;&#4635;&#4653;&#4763;';

        // Arabic
        $supportedLanguages['ar']['description'] = $this->_('Arabic');
        $supportedLanguages['ar']['nativedescription'] = '&#1593;&#1614;&#1585;&#1614;&#1576;&#1610;&#1618;';

        // Armenian
        $supportedLanguages['hy']['description'] = $this->_('Armenian');
        $supportedLanguages['hy']['nativedescription'] = '&#1392;&#1377;&#1397;&#1381;&#1408;&#1381;&#1398;';

        // Basque
        $supportedLanguages['eu']['description'] = $this->_('Basque');
        $supportedLanguages['eu']['nativedescription'] = 'Euskara';

        // Belarusian
        $supportedLanguages['be']['description'] = $this->_('Belarusian');
        $supportedLanguages['be']['nativedescription'] = '&#1041;&#1077;&#1083;&#1072;&#1088;&#1091;&#1089;&#1082;&#1110;';

        // Bengali
        $supportedLanguages['bn']['description'] = $this->_('Bengali');
        $supportedLanguages['bn']['nativedescription'] = '&#2476;&#2494;&#2434;&#2482;&#2494;';

        // Bosnian
        $supportedLanguages['bs']['description'] = $this->_('Bosnian');
        $supportedLanguages['bs']['nativedescription'] = 'Bosanski';

        // Bulgarian
        $supportedLanguages['bg']['description'] = $this->_('Bulgarian');
        $supportedLanguages['bg']['nativedescription'] = '&#x0411;&#x044a;&#x043b;&#x0433;&#x0430;&#x0440;&#x0441;&#x043a;&#x0438;';

        // Catalan
        $supportedLanguages['ca-valencia']['description'] = $this->_('Catalan (Valencian)');
        $supportedLanguages['ca-valencia']['nativedescription'] = 'Catal&#224; (Valenci&#224;)';

        // Catalan
        $supportedLanguages['ca']['description'] = $this->_('Catalan');
        $supportedLanguages['ca']['nativedescription'] = 'Catal&#224;';

        // Welsh
        $supportedLanguages['cy']['description'] = $this->_('Welsh');
        $supportedLanguages['cy']['nativedescription'] = 'Cymraeg';

        // Chinese (Simplified)
        $supportedLanguages['zh-Hans']['description'] = $this->_('Chinese (Simplified)');
        $supportedLanguages['zh-Hans']['nativedescription'] = '&#31616;&#20307;&#20013;&#25991;';

        // Chinese (Traditional - Hong Kong)
        $supportedLanguages['zh-Hant-HK']['description'] = $this->_('Chinese (Traditional - Hong Kong)');
        $supportedLanguages['zh-Hant-HK']['nativedescription'] = '&#32321;&#39636;&#20013;&#25991;&#35486;&#31995;';

        // Chinese (Traditional - Taiwan)
        $supportedLanguages['zh-Hant-TW']['description'] = $this->_('Chinese (Traditional - Taiwan)');
        $supportedLanguages['zh-Hant-TW']['nativedescription'] = '&#32321;&#39636;&#20013;&#25991;&#65288;&#21488;&#28771;&#65289;';

        // Croatian
        $supportedLanguages['hr']['description'] = $this->_('Croatian');
        $supportedLanguages['hr']['nativedescription'] = 'Hrvatski';

        // Czech
        $supportedLanguages['cs']['description'] = $this->_('Czech');
        $supportedLanguages['cs']['nativedescription'] = '&#x010c;esky';

        // Czech informal
        $supportedLanguages['cs-informal']['description'] = $this->_('Czech (informal)');
        $supportedLanguages['cs-informal']['nativedescription'] = '&#x010c;esky neform&aacute;ln&iacute;';

        // Danish
        $supportedLanguages['da']['description'] = $this->_('Danish');
        $supportedLanguages['da']['nativedescription'] = 'Dansk';

        // Dari
        $supportedLanguages['prs']['description'] = $this->_('Dari');
        $supportedLanguages['prs']['nativedescription'] = '&#1583;&#1585;&#1740;';

        // Dutch
        $supportedLanguages['nl']['description'] = $this->_('Dutch');
        $supportedLanguages['nl']['nativedescription'] = 'Nederlands';

        // Dutch
        $supportedLanguages['nl-informal']['description'] = $this->_('Dutch (informal)');
        $supportedLanguages['nl-informal']['nativedescription'] = 'Nederlands (informeel)';

        // English
        $supportedLanguages['en']['description'] = $this->_('English');
        $supportedLanguages['en']['nativedescription'] = 'English';

        // Estonian
        $supportedLanguages['et']['description'] = $this->_('Estonian');
        $supportedLanguages['et']['nativedescription'] = 'Eesti';

        // Finnish
        $supportedLanguages['fi']['description'] = $this->_('Finnish');
        $supportedLanguages['fi']['nativedescription'] = 'Suomi';

        // French
        $supportedLanguages['fr']['description'] = $this->_('French');
        $supportedLanguages['fr']['nativedescription'] = 'Fran&#231;ais';

        // Fula
        $supportedLanguages['ful']['description'] = $this->_('Fula');
        $supportedLanguages['ful']['nativedescription'] = 'Fulfulde';

        // Galician
        $supportedLanguages['gl']['description'] = $this->_('Galician');
        $supportedLanguages['gl']['nativedescription'] = 'Galego';

        // Georgian
        $supportedLanguages['ka']['description'] = $this->_('Georgian');
        $supportedLanguages['ka']['nativedescription'] = '&#4325;&#4304;&#4320;&#4311;&#4323;&#4314;&#4312; &#4308;&#4316;&#4304;';

        // German
        $supportedLanguages['de']['description'] = $this->_('German');
        $supportedLanguages['de']['nativedescription'] = 'Deutsch';

        // German informal
        $supportedLanguages['de-informal']['description'] = $this->_('German (informal)');
        $supportedLanguages['de-informal']['nativedescription'] = 'Deutsch (Du)';

        // Gujarati
        $supportedLanguages['gu']['description'] = $this->_('Gujarati');
        $supportedLanguages['gu']['nativedescription'] = '&#2711;&#2753;&#2716;&#2736;&#2750;&#2724;&#2752;';

        // Greek
        $supportedLanguages['el']['description'] = $this->_('Greek');
        $supportedLanguages['el']['nativedescription'] = '&#917;&#955;&#955;&#951;&#957;&#953;&#954;&#940;';

        // Hindi
        $supportedLanguages['hi']['description'] = $this->_('Hindi');
        $supportedLanguages['hi']['nativedescription'] = '&#2361;&#2367;&#2344;&#2381;&#2342;&#2368;';

        // Hebrew
        $supportedLanguages['he']['description'] = $this->_('Hebrew');
        $supportedLanguages['he']['nativedescription'] = ' &#1506;&#1489;&#1512;&#1497;&#1514;';

        // Hungarian
        $supportedLanguages['hu']['description'] = $this->_('Hungarian');
        $supportedLanguages['hu']['nativedescription'] = 'Magyar';

        // Icelandic
        $supportedLanguages['is']['description'] = $this->_('Icelandic');
        $supportedLanguages['is']['nativedescription'] = '&#237;slenska';

        // Indonesian
        $supportedLanguages['id']['description'] = $this->_('Indonesian');
        $supportedLanguages['id']['nativedescription'] = 'Bahasa Indonesia';

        // Irish
        $supportedLanguages['ie']['description'] = $this->_('Irish');
        $supportedLanguages['ie']['nativedescription'] = 'Gaeilge';

        // Italian
        $supportedLanguages['it']['description'] = $this->_('Italian');
        $supportedLanguages['it']['nativedescription'] = 'Italiano';

        // Italian informal
        $supportedLanguages['it-informal']['description'] = $this->_('Italian (informal)');
        $supportedLanguages['it-informal']['nativedescription'] = 'Italiano (informale)';

        // Japanese
        $supportedLanguages['ja']['description'] = $this->_('Japanese');
        $supportedLanguages['ja']['nativedescription'] = '&#x65e5;&#x672c;&#x8a9e;';

        // Kinyarwanda
        $supportedLanguages['rw']['description'] = $this->_('Kinyarwanda');
        $supportedLanguages['rw']['nativedescription'] = 'Kinyarwanda';

        // Korean
        $supportedLanguages['ko']['description'] = $this->_('Korean');
        $supportedLanguages['ko']['nativedescription'] = '&#54620;&#44397;&#50612;';

        // Kirundi
        $supportedLanguages['run']['description'] = $this->_('Kirundi');
        $supportedLanguages['run']['nativedescription'] = 'Ikirundi';

        // Kurdish (Sorani)
        $supportedLanguages['ckb']['description'] = $this->_('Kurdish (Sorani)');
        $supportedLanguages['ckb']['nativedescription'] = '&#1705;&#1608;&#1585;&#1583;&#1740;&#1740; &#1606;&#1575;&#1608;&#1749;&#1606;&#1583;&#1740;';

        // Kyrgyz
        $supportedLanguages['ky']['description'] = $this->_('Kyrgyz');
        $supportedLanguages['ky']['nativedescription'] = '&#1050;&#1099;&#1088;&#1075;&#1099;&#1079;&#1095;&#1072;';

        // Luxembourgish
        $supportedLanguages['lb']['description'] = $this->_('Luxembourgish');
        $supportedLanguages['lb']['nativedescription'] = 'L&#235;tzebuergesch';

        // Lithuanian
        $supportedLanguages['lt']['description'] = $this->_('Lithuanian');
        $supportedLanguages['lt']['nativedescription'] = 'Lietuvi&#371;';

        // Latvian
        $supportedLanguages['lv']['description'] = $this->_('Latvian');
        $supportedLanguages['lv']['nativedescription'] = 'Latvie&#353;u';

        // Macedonian
        $supportedLanguages['mk']['description'] = $this->_('Macedonian');
        $supportedLanguages['mk']['nativedescription'] = '&#1052;&#1072;&#1082;&#1077;&#1076;&#1086;&#1085;&#1089;&#1082;&#1080;';

        // Mongolian
        $supportedLanguages['mn']['description'] = $this->_('Mongolian');
        $supportedLanguages['mn']['nativedescription'] = '&#1052;&#1086;&#1085;&#1075;&#1086;&#1083;';

        // Malay
        $supportedLanguages['ms']['description'] = $this->_('Malay');
        $supportedLanguages['ms']['nativedescription'] = 'Bahasa Melayu';

        // Malayalam
        $supportedLanguages['ml']['description'] =  $this->_('Malayalam');
        $supportedLanguages['ml']['nativedescription'] = 'Malay&#257;&#7735;a&#7745;';

        // Maltese
        $supportedLanguages['mt']['description'] = $this->_('Maltese');
        $supportedLanguages['mt']['nativedescription'] = 'Malti';

        // Maltese
        $supportedLanguages['mt']['description'] = $this->_('Maltese');
        $supportedLanguages['mt']['nativedescription'] = 'Malti';

        // Marathi
        $supportedLanguages['mr']['description'] = $this->_('Marathi');
        $supportedLanguages['mr']['nativedescription'] = '&#2350;&#2352;&#2366;&#2336;&#2368;';

        // Montenegrin
        $supportedLanguages['cnr']['description'] = $this->_('Montenegrin');
        $supportedLanguages['cnr']['nativedescription'] = 'Crnogorski';

        // Myanmar / Burmese
        $supportedLanguages['mya']['description'] = $this->_('Myanmar (Burmese)');
        $supportedLanguages['mya']['nativedescription'] = '&#4121;&#4156;&#4116;&#4154;&#4121;&#4140;&#4120;&#4140;&#4126;&#4140;';

        // Norwegian Bokmal
        $supportedLanguages['nb']['description'] = $this->_('Norwegian (Bokmal)');
        $supportedLanguages['nb']['nativedescription'] = 'Norsk Bokm&#229;l';

        // Norwegian Nynorsk
        $supportedLanguages['nn']['description'] = $this->_('Norwegian (Nynorsk)');
        $supportedLanguages['nn']['nativedescription'] = 'Norsk Nynorsk';

        // Occitan
        $supportedLanguages['oc']['description'] = $this->_('Occitan');
        $supportedLanguages['oc']['nativedescription'] = "Lenga d'&#242;c";

        // Pashto
        $supportedLanguages['ps']['description'] = $this->_('Pashto');
        $supportedLanguages['ps']['nativedescription'] = '&#1662;&#1690;&#1578;&#1608;';

        // Persian
        $supportedLanguages['fa']['description'] = $this->_('Persian');
        $supportedLanguages['fa']['nativedescription'] = '&#1601;&#1575;&#1585;&#1587;&#1740;';

        // Papiamento (Curacao and Bonaire)
        $supportedLanguages['pap-CW']['description'] = $this->_('Papiamento (Curaçao & Bonaire)');
        $supportedLanguages['pap-CW']['nativedescription'] = 'Papiamentu';

        // Polish
        $supportedLanguages['pl']['description'] = $this->_('Polish');
        $supportedLanguages['pl']['nativedescription'] = 'Polski';

        // Polish
        $supportedLanguages['pl-informal']['description'] = $this->_('Polish (Informal)');
        $supportedLanguages['pl-informal']['nativedescription'] = 'Polski (nieformalny)';

        // Portuguese
        $supportedLanguages['pt']['description'] = $this->_('Portuguese');
        $supportedLanguages['pt']['nativedescription'] = 'Portugu&#234;s';

        // Brazilian Portuguese
        $supportedLanguages['pt-BR']['description'] = $this->_('Portuguese (Brazilian)');
        $supportedLanguages['pt-BR']['nativedescription'] = 'Portugu&#234;s do Brasil';

        // Punjabi
        $supportedLanguages['pa']['description'] = $this->_('Punjabi');
        $supportedLanguages['pa']['nativedescription'] = '&#2602;&#2672;&#2588;&#2622;&#2604;&#2624;';

        // Romanian
        $supportedLanguages['ro']['description'] = $this->_('Romanian');
        $supportedLanguages['ro']['nativedescription'] = 'Rom&#226;na';

        // Russian
        $supportedLanguages['ru']['description'] = $this->_('Russian');
        $supportedLanguages['ru']['nativedescription'] = '&#1056;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;';

        // Sinhala
        $supportedLanguages['si']['description'] = $this->_('Sinhala');
        $supportedLanguages['si']['nativedescription'] = '&#3523;&#3538;&#3458;&#3524;&#3517;';

        // Slovak
        $supportedLanguages['sk']['description'] = $this->_('Slovak');
        $supportedLanguages['sk']['nativedescription'] = 'Sloven&#269;ina';

        // Slovenian
        $supportedLanguages['sl']['description'] = $this->_('Slovenian');
        $supportedLanguages['sl']['nativedescription'] = 'Sloven&#353;&#269;ina';

        // Serbian
        $supportedLanguages['sr']['description'] = $this->_('Serbian (Cyrillic)');
        $supportedLanguages['sr']['nativedescription'] = '&#1057;&#1088;&#1087;&#1089;&#1082;&#1080;';

        // Serbian (Latin script)
        $supportedLanguages['sr-Latn']['description'] = $this->_('Serbian (Latin)');
        $supportedLanguages['sr-Latn']['nativedescription'] = 'Srpski';

        // Spanish
        $supportedLanguages['es']['description'] = $this->_('Spanish');
        $supportedLanguages['es']['nativedescription'] = 'Espa&#241;ol';

        // Spanish (Argentina)
        $supportedLanguages['es-AR']['description'] = $this->_('Spanish (Argentina)');
        $supportedLanguages['es-AR']['nativedescription'] = 'Espa&#241;ol rioplatense';

        // Spanish (Argentina) (Informal)
        $supportedLanguages['es-AR-informal']['description'] = $this->_('Spanish (Argentina) (Informal)');
        $supportedLanguages['es-AR-informal']['nativedescription'] = 'Espa&#241;ol rioplatense informal';

        // Spanish (Chile)
        $supportedLanguages['es-CL']['description'] = $this->_('Spanish (Chile)');
        $supportedLanguages['es-CL']['nativedescription'] = 'Espa&#241;ol chileno';

        // Spanish (Mexico)
        $supportedLanguages['es-MX']['description'] = $this->_('Spanish (Mexico)');
        $supportedLanguages['es-MX']['nativedescription'] = 'Espa&#241;ol mexicano';

        // Swahili
        $supportedLanguages['swh']['description'] = $this->_('Swahili');
        $supportedLanguages['swh']['nativedescription'] = 'Kiswahili';

        // Swedish
        $supportedLanguages['sv']['description'] = $this->_('Swedish');
        $supportedLanguages['sv']['nativedescription'] = 'Svenska';

        // Tajik
        $supportedLanguages['tg']['description'] = $this->_('Tajik');
        $supportedLanguages['tg']['nativedescription'] = '&#x422;&#x43E;&#x4B7;&#x438;&#x43A;&#x4E3;';

        // Tamil
        $supportedLanguages['ta']['description'] = $this->_('Tamil');
        $supportedLanguages['ta']['nativedescription'] = '&#2980;&#2990;&#3007;&#2996;&#3021;';

        // Turkish
        $supportedLanguages['tr']['description'] = $this->_('Turkish');
        $supportedLanguages['tr']['nativedescription'] = 'T&#252;rk&#231;e';

        // Thai
        $supportedLanguages['th']['description'] = $this->_('Thai');
        $supportedLanguages['th']['nativedescription'] = '&#3616;&#3634;&#3625;&#3634;&#3652;&#3607;&#3618;';

        //Ukrainian
        $supportedLanguages['uk']['description'] = $this->_('Ukrainian');
        $supportedLanguages['uk']['nativedescription'] = '&#x423;&#x43A;&#x440;&#x430;&#x457;&#x43D;&#x441;&#x44C;&#x43A;&#x430;';

        //Urdu
        $supportedLanguages['ur']['description'] = $this->_('Urdu');
        $supportedLanguages['ur']['nativedescription'] = '&#1575;&#1585;&#1583;&#1608;';

        // Vietnamese
        $supportedLanguages['vi']['description'] = $this->_('Vietnamese');
        $supportedLanguages['vi']['nativedescription'] = 'Ti&#7871;ng Vi&#7879;t';

        // Zulu
        $supportedLanguages['zu']['description'] = $this->_('Zulu');
        $supportedLanguages['zu']['nativedescription'] = 'isiZulu';

        return $supportedLanguages;
    }
}
