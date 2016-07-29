<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets_Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

namespace Gems\Snippets\Tracker;

/**
 * Shows the survey rounds in a track
 *
 * @package    Gems
 * @subpackage Snippets_Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class TrackSurveyOverviewSnippet extends \Gems_Snippets_MenuSnippetAbstract
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * Optional: alternative source for the data above
     *
     * @var array
     */
    protected $trackData;

    /**
     * Optional, can be source of the $trackId
     *
     * @var \Gems_Tracker_Engine_TrackEngineInterface
     */
    protected $trackEngine;

    /**
     * REQUIRED: the id of the track shown
     *
     * Or must be extracted from $trackData or $trackEngine
     *
     * @var int
     */
    protected $trackId;

    /**
     * Optional: the name of the track
     *
     * @var int
     */
    public $trackName;

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $html = $this->getHtmlSequence();
        if ($this->trackName) {
            $html->h3(sprintf($this->_('Surveys in %s track'), $this->trackName));
        }

        $trackRepeater = $this->getRepeater($this->trackId);
        $table = $html->div(array('class' => 'table-container'))->table($trackRepeater, array('class' => 'browser table'));

        if ($link = $this->findMenuItem('project-tracks', 'questions')) {
            $table->tr()->onclick = array('location.href=\'', $link->toHRefAttribute($trackRepeater), '\';');
            $table->addColumn($link->toActionLinkLower($trackRepeater));
        }

        $surveyName[] = $trackRepeater->gsu_survey_name;
        $surveyName[] = \MUtil_Lazy::iif($trackRepeater->gro_icon_file, \MUtil_Html::create('img', array('src' => $trackRepeater->gro_icon_file, 'class' => 'icon')));

        $table->addColumn($surveyName,                           $this->_('Survey'));
        $table->addColumn($trackRepeater->gro_round_description, $this->_('Details'));
        $table->addColumn($trackRepeater->ggp_name,              $this->_('By'));
        $table->addColumn($trackRepeater->gsu_survey_description->call(array(__CLASS__, 'oneLine')),
                                                                 $this->_('Description'));
        return $html;
    }

    private function getRepeater($trackId)
    {
        if (!($this->trackEngine instanceof \Gems_Tracker_Engine_TrackEngineInterface)) {
            $this->trackEngine = $this->loader->getTracker()->getTrackEngine($trackId);
        }

        $roundModel = $this->trackEngine->getRoundModel(true, null);

        return $roundModel->loadRepeatable(array('gro_id_track' => $trackId, 'gro_active' => 1));
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        if (! $this->trackId) {
            if (isset($this->trackData['gtr_id_track'])) {
                $this->trackId = $this->trackData['gtr_id_track'];
            } elseif ($this->trackEngine instanceof \Gems_Tracker_Engine_TrackEngineInterface) {
                $this->trackId = $this->trackEngine->getTrackId();
            }
        }
        if (! $this->trackName) {
            if (isset($this->trackData['gtr_track_name'])) {
                $this->trackName = $this->trackData['gtr_track_name'];
            } elseif ($this->trackEngine instanceof \Gems_Tracker_Engine_TrackEngineInterface) {
                $this->trackName = $this->trackEngine->getTrackName();
            }
        }
        return (boolean) $this->trackName && parent::hasHtmlOutput();
    }

    public static function oneLine($line)
    {
        if (strlen($line) > 2) {
            if ($p = strpos($line, '<', 1)) {
                $line = substr($line, 0, $p);
            }
            if ($p = strpos($line, "\n", 1)) {
                $line = substr($line, 0, $p);
            }
        }

        return \MUtil_Html::raw(trim($line));
    }
}
