<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Track
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2019 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Track;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Track
 * @copyright  Copyright (c) 2019 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.6
 */
class TrackDeleteSnippet extends \Gems\Snippets\ModelItemYesNoDeleteSnippetAbstract {
    
    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;
    
    /**
     *
     * @var \Gems\Tracker\Model\TrackModel
     */
    protected $model;

    /**
     *
     * @var int
     */
    protected $trackId;

    /**
     * The number of times someone started answering a round in this track
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
        if (! $this->model instanceof \Gems\Tracker\Model\TrackModel) {
            $this->model = $this->loader->getTracker()->getTrackModel();
        }

        return $this->model;
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
        if ($model instanceof \Gems\Tracker\Model\TrackModel) {
            $this->useCount = $model->getStartCount($this->trackId);

            if ($this->useCount) {
                $this->addMessage(sprintf($this->plural(
                        'This track has been started %s time.', 'This track has been started %s times.',
                        $this->useCount
                        ), $this->useCount));
                $this->addMessage($this->_('This track cannot be deleted, only deactivated.'));

                $this->deleteQuestion = $this->_('Do you want to deactivate this track?');
                $this->displayTitle   = $this->_('Deactivate track');
            }
        }

        parent::setShowTableFooter($bridge, $model);
    }
}
