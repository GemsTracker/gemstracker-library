<?php

/**
 *
 * @package    Gems
 * @subpackage Events
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Put the highest value first
 *
 * @package    Gems
 * @subpackage Events
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.7
 */
class Gems_Event_Survey_Display_AllSingleTrack extends \MUtil_Translate_TranslateableAbstract
    implements \Gems_Event_SurveyDisplayEventInterface
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = array(
        'grc_success' => SORT_DESC,
        'gto_valid_from' => SORT_ASC,
        'gto_completion_time' => SORT_ASC,
        'gto_round_order' => SORT_ASC);

    /**
     * Function that returns the snippets to use for this display.
     *
     * @param \Gems_Tracker_Token $token The token to get the snippets for
     * @return array of Snippet names or nothing
     */
    public function getAnswerDisplaySnippets(\Gems_Tracker_Token $token)
    {
        return 'Tracker_Answers_SingleTrackAnswersModelSnippet';
    }

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName()
    {
        return $this->_('Show all tokens for this survey in this track type');
    }
}
