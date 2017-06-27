<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: SingleSurveyAvailableTracksSnippet.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Snippets\Tracker;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 6-mei-2015 16:36:17
 */
class SingleSurveyAvailableTracksSnippet extends AvailableTracksSnippet
{
    /**
     * Are we working in a multi tracks environment?
     *
     * @var boolean
     */
    protected $multiTracks = true;

    /**
     * The respondent2track
     *
     * @var \Gems_Tracker_RespondentTrack
     */
    protected $respondentTrack;

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        $model = parent::createModel();
        $model->addColumn(new \Zend_Db_Expr(1), 'track_can_be_created');

        return $model;
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
        return ($this->respondent instanceof \Gems_Tracker_Respondent) &&
                $this->respondent->exists &&
                (! ($this->multiTracks || ($this->respondentTrack instanceof \Gems_Tracker_RespondentTrack)));
    }
}
