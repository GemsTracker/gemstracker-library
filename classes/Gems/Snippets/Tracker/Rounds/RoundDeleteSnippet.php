<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker\Rounds;

use Gems\Tracker\Model\RoundModel;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 22-apr-2015 15:32:11
 */
class RoundDeleteSnippet extends \Gems\Snippets\ModelItemYesNoDeleteSnippetAbstract
{
    /**
     *
     * @var \Gems\Tracker\Model\RoundModel
     */
    protected $model;

    /**
     *
     * @var int
     */
    protected $roundId;

    /**
     * Required: the engine of the current track
     *
     * @var \Gems\Tracker\Engine\TrackEngineInterface
     */
    protected $trackEngine;

    /**
     *
     * @var int
     */
    protected $trackId;

    /**
     * The number of times someone started answering this round
     *
     * @var int
     */
    protected $useCount = 0;

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel()
    {
        if (! $this->model instanceof RoundModel) {
            $this->model = $this->trackEngine->getRoundModel(false, 'index');
        }

        // Now add the joins so we can sort on the real name
        // $this->model->addTable('gems__surveys', array('gro_id_survey' => 'gsu_id_survey'));

        // $this->model->set('gsu_survey_name', $this->model->get('gro_id_survey'));

        return $this->model;
    }

    /**
     * Set what to do when the form is 'finished'.
     *
     * @return \MUtil\Snippets\ModelYesNoDeleteSnippetAbstract
     */
    protected function setAfterDeleteRoute()
    {
        parent::setAfterDeleteRoute();

        if (is_array($this->afterSaveRouteUrl)) {
            $this->afterSaveRouteUrl[\MUtil\Model::REQUEST_ID] = $this->trackId;
            $this->afterSaveRouteUrl[\Gems\Model::ROUND_ID]    = null;
            $this->afterSaveRouteUrl[$this->confirmParameter]  = null;
        }
    }

    /**
     * Set the footer of the browse table.
     *
     * Overrule this function to set the header differently, without
     * having to recode the core table building code.
     *
     * @param \MUtil\Model\Bridge\VerticalTableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @return void
     */
    protected function setShowTableFooter(\MUtil\Model\Bridge\VerticalTableBridge $bridge, \MUtil\Model\ModelAbstract $model)
    {
        if ($model instanceof RoundModel) {
            $refCount = $model->getRefCount($this->roundId);
            if ($refCount) {
                $this->addMessage(sprintf($this->plural(
                    'This round is used %s time in another round.', 'This round is used %s times in other rounds.',
                    $refCount
                ), $refCount));
            }
            
            $this->useCount = $model->getStartCount($this->roundId);
            
            if ($this->useCount) {
                $this->addMessage(sprintf($this->plural(
                    'This round has been completed %s time.', 'This round has been completed %s times.',
                    $this->useCount
                ), $this->useCount));
            }

            \MUtil\EchoOut\EchoOut::track($refCount, $this->useCount);
            if ($refCount || $this->useCount) {
                $this->addMessage($this->_('This round cannot be deleted, only deactivated.'));
                $this->deleteQuestion = $this->_('Do you want to deactivate this round?');
                $this->displayTitle   = $this->_('Deactivate round');
            }
        }

        parent::setShowTableFooter($bridge, $model);
    }
}
