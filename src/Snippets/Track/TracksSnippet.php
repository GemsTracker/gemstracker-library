<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Track
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Track;

use Gems\Menu\MenuSnippetHelper;
use Gems\Tracker;
use Gems\Util\Translated;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Bridge\BridgeInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Track
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Mar 10, 2016 6:14:24 PM
 */
class TracksSnippet extends \Gems\Snippets\ModelTableSnippetAbstract
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = array(
        'gr2t_start_date' => SORT_ASC,
    );

    /**
     * One of the \MUtil\Model\Bridge\BridgeAbstract MODE constants
     *
     * @var int
     */
    protected $bridgeMode = BridgeInterface::MODE_ROWS;

    /**
     * Menu actions to show in Edit box.
     *
     * If controller is numeric $menuActionController is used, otherwise
     * the key specifies the controller.
     */
    public array $menuEditRoutes = ['respondent.tracks.edit-track'];

    /**
     * Menu actions to show in Show box.
     *
     * If controller is numeric $menuActionController is used, otherwise
     * the key specifies the controller.
     */
    public array $menuShowRoutes = ['respondent.tracks.show'];

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        MenuSnippetHelper $menuHelper,
        TranslatorInterface $translate,
        protected Tracker $tracker,
        protected Translated $translatedUtil,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $menuHelper, $translate);
    }

    protected function createModel(): DataReaderInterface
    {
        $model = $this->tracker->getRespondentTrackModel();

        $model->addColumn('CONCAT(gr2t_completed, \'' . $this->_(' of ') . '\', gr2t_count)', 'progress');

        $model->getMetaModel()->resetOrder();
        $model->getMetaModel()->set('gtr_track_name', ['label' => $this->_('Track')]);
        $model->getMetaModel()->set('gr2t_track_info', ['label' => $this->_('Description')]);
        $model->getMetaModel()->set('gr2t_start_date', [
            'label' => $this->_('Start'),
            'formatFunction' => $this->translatedUtil->formatDate,
            'default' => new \DateTimeImmutable()
        ]);
        $model->getMetaModel()->set('gr2t_reception_code');
        $model->getMetaModel()->set('progress', [
            'label' => $this->_('Progress'),
//            'tdClass' => 'rightAlign',
//            'thClass' => 'rightAlign'
        ]);
        $model->getMetaModel()->set('assigned_by', ['label' => $this->_('Assigned by')]);

        return $model;
    }
}
