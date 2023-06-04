<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker\Rounds
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker\Rounds;

use Gems\Condition\ConditionInterface;
use Gems\Condition\RoundConditionInterface;
use Gems\Menu\MenuSnippetHelper;
use Gems\Menu\RouteHelper;
use Gems\Snippets\ModelTableSnippetAbstract;
use Gems\Tracker\Model\RoundModel;
use Gems\Util\Translated;
use MUtil\Model;
use MUtil\Translate\Translator;
use Zalt\Base\RequestInfo;
use Zalt\Model\Bridge\BridgeAbstract;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker\Rounds
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.4 27-Nov-2018 11:57:15
 */
class ConditionRoundsTableSnippet extends ModelTableSnippetAbstract
{
    public $browse = true;

    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = [
        'gtr_track_name' => SORT_ASC,
        'gro_id_order'   => SORT_ASC,
    ];

    /**
     *
     * @var \Gems\Model\JoinModel
     */
    protected $_model;

    /**
     * One of the \MUtil\Model\Bridge\BridgeAbstract MODE constants
     *
     * @var int
     */
    protected $bridgeMode = BridgeAbstract::MODE_ROWS;

    /**
     * @var ConditionInterface
     */
    protected ?ConditionInterface $condition = null;

    /**
     * The default controller for menu actions, if null the current controller is used.
     *
     * @var array (int/controller => action)
     */
    public array $menuActionController = ['track-rounds'];

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        MenuSnippetHelper $menuSnippetHelper,
        Translator $translate,
        protected Translated $translatedUtil

    ) {
        parent::__construct($snippetOptions, $requestInfo, $menuSnippetHelper, $translate);
        $this->caption = $this->translate->_('Rounds with this condition');
        $this->onEmpty = $this->translate->_('No rounds using this condition found');

    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel(): DataReaderInterface
    {
        if (! $this->_model instanceof RoundModel) {
            $this->_model = new RoundModel();

            $this->_model->addTable('gems__tracks', ['gro_id_track' => 'gtr_id_track']);
            $this->_model->addTable('gems__surveys', ['gro_id_survey' => 'gsu_id_survey']);
            $this->_model->addLeftTable('gems__groups', ['gsu_id_primary_group' => 'ggp_id_group']);

            $this->_model->addColumn("CASE WHEN gro_active = 1 THEN '' ELSE 'deleted' END", 'row_class');

            $this->_model->set('gro_id_round');
            $this->_model->set('gtr_track_name',        'label', $this->_('Track name'));
            $this->_model->set('gro_id_order',          'label', $this->_('Round order'));
            $this->_model->set('gro_round_description', 'label', $this->_('Description'));
            $this->_model->set('gsu_survey_name',       'label', $this->_('Survey'));
            $this->_model->set('ggp_name',              'label', $this->_('Assigned to'));
            $this->_model->set('gro_active',            'label', $this->_('Active'),
                    'multiOptions', $this->translatedUtil->getYesNo());
        }

        // Now add the joins so we can sort on the real name
        //

        // $this->_model->set('gsu_survey_name', $this->_model->get('gro_id_survey'));

        return $this->_model;
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
    public function hasHtmlOutput(): bool
    {
        if ($this->condition instanceof RoundConditionInterface) {
            return true;
        }

        return ! $this->condition;
    }

    /**
     * Overrule to implement snippet specific filtering and sorting.
     *
     * @param MetaModelInterface $metaModel
     */
    public function getFilter(MetaModelInterface $metaModel): array
    {
        $filter = parent::getFilter($metaModel);

        $conditionId = null;
        if ($this->condition) {
            $conditionId = $this->condition->getConditionId();
        } else {
            $queryParams = $this->requestInfo->getRequestQueryParams();
            if (isset($queryParams[Model::REQUEST_ID])) {
                $conditionId = $queryParams[Model::REQUEST_ID];
            }
        }

        //\MUtil\Model::$verbose = true;
        if ($conditionId) {
            $filter['gro_condition'] = $conditionId;
        }

        return $filter;
    }
}
