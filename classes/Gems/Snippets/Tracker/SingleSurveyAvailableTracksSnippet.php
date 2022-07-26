<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
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
     * @var \Gems\Tracker\RespondentTrack
     */
    protected $respondentTrack;

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
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
     * {@see \MUtil\Registry\TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        return ($this->respondent instanceof \Gems\Tracker\Respondent) &&
                $this->respondent->exists &&
                (! ($this->multiTracks || ($this->respondentTrack instanceof \Gems\Tracker\RespondentTrack)));
    }
}
