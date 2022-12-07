<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets_Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker;

use Gems\MenuNew\MenuSnippetHelper;
use Gems\Tracker;
use Gems\Tracker\Engine\TrackEngineInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * Shows the survey rounds in a track
 *
 * @package    Gems
 * @subpackage Snippets_Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class TrackSurveyOverviewSnippet extends \Zalt\Snippets\TranslatableSnippetAbstract
{
    /**
     * Optional: alternative source for the data above
     *
     * @var array
     */
    protected array $trackData = [];

    /**
     * Optional, can be source of the $trackId
     *
     * @var \Gems\Tracker\Engine\TrackEngineInterface
     */
    protected TrackEngineInterface $trackEngine;

    /**
     * REQUIRED: the id of the track shown
     *
     * Or must be extracted from $trackData or $trackEngine
     *
     * @var int
     */
    protected int $trackId;

    /**
     * Optional: the name of the track
     *
     * @var int
     */
    protected string $trackName;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        protected MenuSnippetHelper $menuHelper,
        protected Tracker $tracker,
        )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate);
    }
    
    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     */
    public function getHtmlOutput()
    {
        $html = $this->getHtmlSequence();
        if ($this->trackName) {
            $html->h3(sprintf($this->_('Surveys in %s track'), $this->trackName));
        }

        $trackRepeater = $this->getRepeater($this->trackId);
        $table = $html->div(array('class' => 'table-container'))->table($trackRepeater, array('class' => 'browser table'));
        $table->setOnEmpty($this->_('No surveys in track'));

        /*if ($link = $this->findMenuItem('project-tracks', 'questions')) {
            $table->tr()->onclick = array('location.href=\'', $link->toHRefAttribute($trackRepeater), '\';');
            $table->addColumn($link->toActionLinkLower($trackRepeater));
        }*/

        $surveyName[] = $trackRepeater->gsu_survey_name;
        $surveyName[] = \MUtil\Lazy::iif($trackRepeater->gro_icon_file, \MUtil\Html::create('img', array('src' => $trackRepeater->gro_icon_file, 'class' => 'icon')));

        $table->addColumn($surveyName,                           $this->_('Survey'));
        $table->addColumn($trackRepeater->gro_round_description, $this->_('Details'));
        $table->addColumn($trackRepeater->ggp_name,              $this->_('By'));
        $table->addColumn($trackRepeater->gsu_survey_description->call(array(__CLASS__, 'oneLine')),
                                                                 $this->_('Description'));
        return $html;
    }

    private function getRepeater($trackId)
    {
        if (! (isset($this->trackEngine) && $this->trackEngine instanceof TrackEngineInterface)) {
            $this->trackEngine = $this->tracker->getTrackEngine($trackId);
        }

        $roundModel = $this->trackEngine->getRoundModel(true, null);
        
        // The conditions seem to break the iterator, load only fields we need for display so the conditions won't be triggered
        $roundModel->trackUsage();
        $roundModel->get('gro_id_track');
        $roundModel->get('gsu_id_survey');
        $roundModel->get('gsu_survey_name');
        $roundModel->get('gro_icon_file');
        $roundModel->get('gro_round_description');
        $roundModel->get('ggp_name');
        $roundModel->get('gsu_survey_description');
        
        return $roundModel->loadRepeatable(array('gro_id_track' => $trackId, 'gro_active' => 1));
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil\Registry\TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput(): bool
    {
        if (! isset($this->trackId)) {
            if (isset($this->trackData['gtr_id_track'])) {
                $this->trackId = $this->trackData['gtr_id_track'];
            } elseif (isset($this->trackEngine) && $this->trackEngine instanceof TrackEngineInterface) {
                $this->trackId = $this->trackEngine->getTrackId();
            }
        }
        if (! isset($this->trackName)) {
            if (isset($this->trackData['gtr_track_name'])) {
                $this->trackName = $this->trackData['gtr_track_name'];
            } elseif (isset($this->trackEngine) && $this->trackEngine instanceof TrackEngineInterface) {
                $this->trackName = $this->trackEngine->getTrackName();
            }
        }
        return (boolean) isset($this->trackName) && parent::hasHtmlOutput();
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

        return \MUtil\Html::raw(trim($line));
    }
}
