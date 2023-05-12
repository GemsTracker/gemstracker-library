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

use Gems\Loader;
use Gems\Snippets\ModelItemYesNoDeleteSnippetAbstract;
use Gems\Tracker\Model\TrackModel;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\Snippets\ModelBridge\DetailTableBridge;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Track
 * @copyright  Copyright (c) 2019 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.6
 */
class TrackDeleteSnippet extends ModelItemYesNoDeleteSnippetAbstract
{
    
    /**
     *
     * @var Loader
     */
    protected $loader;
    
    /**
     *
     * @var TrackModel
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
     * @return FullDataInterface
     */
    protected function createModel(): FullDataInterface
    {
        if (! $this->model instanceof TrackModel) {
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
     * @param DetailTableBridge $bridge
     * @param DataReaderInterface $dataModel
     * @return void
     */
    protected function setShowTableFooter(DetailTableBridge $bridge, DataReaderInterface $dataModel)
    {
        if ($dataModel instanceof TrackModel) {
            $this->useCount = $dataModel->getStartCount($this->trackId);

            if ($this->useCount) {
                $this->messenger->addMessage(sprintf($this->plural(
                        'This track has been started %s time.', 'This track has been started %s times.',
                        $this->useCount
                        ), $this->useCount));
                $this->messenger->addMessage($this->_('This track cannot be deleted, only deactivated.'));

                $this->deleteQuestion = $this->_('Do you want to deactivate this track?');
                $this->displayTitle   = $this->_('Deactivate track');
            }
        }

        parent::setShowTableFooter($bridge, $dataModel);
    }
}
