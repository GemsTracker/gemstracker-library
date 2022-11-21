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

use Gems\Util\Translated;
use Zalt\Model\Data\DataReaderInterface;

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
    protected $bridgeMode = \MUtil\Model\Bridge\BridgeAbstract::MODE_ROWS;

    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * Menu actions to show in Edit box.
     *
     * If controller is numeric $menuActionController is used, otherwise
     * the key specifies the controller.
     *
     * @var array (int/controller => action)
     */
    public array $menuEditRoutes = array('track' => 'edit-track');

    /**
     * Menu actions to show in Show box.
     *
     * If controller is numeric $menuActionController is used, otherwise
     * the key specifies the controller.
     *
     * @var array (int/controller => action)
     */
    public array $menuShowRoutes = array('track' => 'show-track');

    /**
     * @var Translated
     */
    protected $translatedUtil;

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel(): DataReaderInterface
    {
        $model = $this->loader->getTracker()->getRespondentTrackModel();

        $model->addColumn('CONCAT(gr2t_completed, \'' . $this->_(' of ') . '\', gr2t_count)', 'progress');

        $model->resetOrder();
        $model->set('gtr_track_name',    'label', $this->_('Track'));
        $model->set('gr2t_track_info',   'label', $this->_('Description'));
        $model->set('gr2t_start_date',   'label', $this->_('Start'),
            'formatFunction', $this->translatedUtil->formatDate,
            'default', new \DateTimeImmutable());
        $model->set('gr2t_reception_code');
        $model->set('progress', 'label', $this->_('Progress')); // , 'tdClass', 'rightAlign', 'thClass', 'rightAlign');
        $model->set('assigned_by',       'label', $this->_('Assigned by'));

        return $model;
    }
}
