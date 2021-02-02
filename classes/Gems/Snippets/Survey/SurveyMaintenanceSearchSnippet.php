<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets_Survey
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: SurveyMaintenanceSearchSnippet.php 2532 2015-04-30 16:33:05Z matijsdejong $
 */

namespace Gems\Snippets\Survey;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets_Survey
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 20-feb-2015 13:22:48
 */
class SurveyMaintenanceSearchSnippet extends \Gems_Snippets_AutosearchFormSnippet
{
    /**
     * @var \Gems_Loader
     */
    protected $loader;
    
    /**
     * Returns a text element for autosearch. Can be overruled.
     *
     * The form / html elements to search on. Elements can be grouped by inserting null's between them.
     * That creates a distinct group of elements
     *
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array Of \Zend_Form_Element's or static tekst to add to the html or null for group breaks.
     */
    protected function getAutoSearchElements(array $data)
    {
        $elements = parent::getAutoSearchElements($data);

        $groups     = $this->util->getDbLookup()->getGroups();
        $elements[] = $this->_createSelectElement('gsu_id_primary_group', $groups, $this->_('(all groups)'));

        // If more than one source, allow to filter on it
        $sources = $this->util->getDbLookup()->getSources();
        if (count($sources) > 1) {
            $elements[] = $this->_createSelectElement('gsu_id_source', $sources, $this->_('(all sources)'));    
        }
        
        $languages = $this->util->getTrackData()->getSurveyLanguages();
        $elements[] = $this->_createSelectElement('survey_languages', $languages, $this->_('(all languages)'));

        $states     = array(
            'act' => $this->_('Active'),
            'sok' => $this->_('OK in source, not active'),
            'nok' => $this->_('Blocked in source'),
            'anonymous' => 'Uses anonymous answers',
            'datestamp' => 'Not date stamped',
            'persistance' => 'Token-based persistence is disabled',
            'noattributes' => 'Token attributes could not be created',
            'notable' => 'No token table created',
            'removed' => 'Survey was removed from source',
        );
        $elements[] = $this->_createSelectElement('status', $states, $this->_('(every state)'));
        
        $elements[] = \MUtil_Html::create('br');

        $warnings     = array(
            'withwarning'              => $this->_('(with warnings)'),
            'nowarning'                => $this->_('(without warnings)'),
            'autoredirect'             => 'Auto-redirect is disabled',
            'alloweditaftercompletion' => 'Editing after completion is enabled',
            'allowregister'            => 'Public registration is enabled',
            'listpublic'               => 'Public access is enabled',
        );
        $elements[] = $this->_createSelectElement('survey_warnings', $warnings, $this->_('(every warning state)'));
        $elements[] = $this->_createSelectElement('gsu_mail_code', $this->util->getDbLookup()->getSurveyMailCodes(), $this->_('(all mail codes)'));
        
        $yesNo      = $this->util->getTranslated()->getYesNo();
        $elements[] = $this->_createSelectElement('gsu_insertable', $yesNo, $this->_('(any insertable)'));
        
        $events = $this->loader->getEvents();
        $eList['!Gems_Event_Survey'] = $this->_('(any event)');
        $eList['!Gems_Event_SurveyBeforeAnsweringEventInterface'] = $this->_('(any before answering)');
        $eList += $events->listSurveyBeforeAnsweringEvents();
        $eList['!Gems_Event_SurveyCompletedEventInterface'] = $this->_('(any survey completed)');
        $eList += $events->listSurveyCompletionEvents();
        $eList['!Gems_Event_SurveyDisplayEventInterface'] = $this->_('(any display event)');
        $eList += $events->listSurveyDisplayEvents();
        $elements[] = $this->_createSelectElement('events', $eList, $this->_('(all surveys)'));

        return $elements;
    }
}
