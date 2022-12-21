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

use Gems\MenuNew\MenuSnippetHelper;
use Gems\Snippets\ModelItemYesNoDeleteSnippetAbstract;
use Gems\Tracker\Model\RoundModel;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Message\MessengerInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\Snippets\ModelBridge\DetailTableBridge;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 22-apr-2015 15:32:11
 */
class RoundDeleteSnippet extends ModelItemYesNoDeleteSnippetAbstract
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

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        MenuSnippetHelper $menuHelper,
        TranslatorInterface $translate,
        MessengerInterface $messenger
    ) {
        parent::__construct($snippetOptions, $requestInfo, $menuHelper, $translate, $messenger);
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel(): FullDataInterface
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
        $this->afterActionRouteUrl = $this->menuHelper->getRouteUrl('track-builder.track-maintenance.show', [
            'trackId' => $this->trackId,
        ]);

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
    protected function setShowTableFooter(DetailTableBridge $bridge, DataReaderInterface $model)
    {
        if ($model instanceof RoundModel) {
            $refCount = $model->getRefCount($this->roundId);
            if ($refCount) {
                $this->messenger->addMessage(sprintf($this->plural(
                    'This round is used %s time in another round.', 'This round is used %s times in other rounds.',
                    $refCount
                ), $refCount));
            }
            
            $this->useCount = $model->getStartCount($this->roundId);
            
            if ($this->useCount) {
                $this->messenger->addMessage(sprintf($this->plural(
                    'This round has been completed %s time.', 'This round has been completed %s times.',
                    $this->useCount
                ), $this->useCount));
            }

            if ($refCount || $this->useCount) {
                $this->messenger->addMessage($this->_('This round cannot be deleted, only deactivated.'));
                $this->deleteQuestion = $this->_('Do you want to deactivate this round?');
                $this->displayTitle   = $this->_('Deactivate round');
            }
        }

        parent::setShowTableFooter($bridge, $model);
    }
}
