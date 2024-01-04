<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker\Rounds;

use Gems\Locale\Locale;
use Gems\Menu\RouteHelper;
use Gems\Tracker;
use Gems\Tracker\Engine\StepEngineAbstract;
use Gems\Tracker\Snippets\ShowRoundSnippetAbstract;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\MessengerInterface;
use Zalt\Model\Bridge\BridgeAbstract;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelBridge\DetailTableBridge;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class ShowRoundStepSnippet extends ShowRoundSnippetAbstract
{
    /**
     *
     * @var array
     */
    private ?array $_roundData = null;

    /**
     * One of the \MUtil\Model\Bridge\BridgeAbstract MODE constants
     *
     * @var int
     */
    protected $bridgeMode = BridgeAbstract::MODE_SINGLE_ROW;

    /**
     *
     * @var boolean True when only tracked fields should be retrieved by the nodel
     */
    protected $trackUsage = false;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        RouteHelper $routeHelper,
        Tracker $tracker,
        protected Locale $locale,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $routeHelper, $tracker);
    }

    private function _addIf(array $names, DetailTableBridge $bridge, DataReaderInterface $model)
    {
        $metaModel = $model->getMetaModel();
        foreach ($names as $name) {
            if ($metaModel->has($name, 'label')) {
                $bridge->addItem($name);
            }
        }
    }

    /**
     * Adds rows from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     */
    protected function addShowTableRows(DetailTableBridge $bridge, DataReaderInterface $dataModel): void
    {
        $this->_roundData = $bridge->getRow();

        if ($this->trackEngine instanceof StepEngineAbstract && $this->_roundData) {
            $this->trackEngine->updateRoundModelToItem(
                $dataModel->getMetaModel(),
                $this->_roundData,
                $this->locale->getLanguage()
            );
        }

        $bridge->addItem('gro_id_track');
        $bridge->addItem('gro_id_survey');
        $bridge->addItem('gro_round_description');
        $bridge->addItem('gro_id_order');
        $bridge->addItem('gro_icon_file');

        if ($dataModel->getMetaModel()->has('ggp_name')) {
            $bridge->addItem('ggp_name');
        } elseif ($dataModel->getMetaModel()->has('gro_id_relationfield')) {
            $bridge->addItem('gro_id_relationfield');
        }

        $bridge->addItem('valid_after');

        $this->_addIf(array('gro_valid_after_source', 'gro_valid_after_id', 'gro_valid_after_field'), $bridge, $dataModel);

        $this->_addIf(array('gro_valid_for_source', 'gro_valid_for_id', 'gro_valid_for_field'), $bridge, $dataModel);

        $bridge->addItem('valid_cond');
        $bridge->addItem('condition_display');

        $bridge->addItem('gro_active');
        // Prevent empty row when no changed events exist
        if ($dataModel->getMetaModel()->get('gro_changed_event', 'label')) {
            $bridge->addItem('gro_changed_event');
        }
        $bridge->addItem('gro_code');
        $bridge->addItem('org_specific_round');
        if (isset($this->_roundData['org_specific_round'])) {
            $bridge->addItem('organizations');
        }
    }
}