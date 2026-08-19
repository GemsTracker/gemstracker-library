<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2017 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Respondent;

use Gems\Html;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model\JoinModel;
use Gems\Tracker;
use Gems\User\User;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Late\RepeatableByKeyValue;
use Zalt\Model\Bridge\BridgeAbstract;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @copyright  Copyright (c) 2017 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.2
 */
class RespondentOverviewSnippet extends \Gems\Snippets\ModelTableSnippetAbstract
{
    /**
     * Set a fixed model filter.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedFilter = [
        'gto_completion_time IS NOT NULL',
        'grc_success' => 1,
        ];

    public $bridgeMode = BridgeAbstract::MODE_ROWS;

    protected readonly User $currentUser;

    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    public $extraSort = ['gto_completion_time' => SORT_DESC];

    public array $menuShowRoutes = ['track.answer'];

    public bool $showMenu = false;

    /**
     *
     * @var DataReaderInterface
     */
    protected $model;

    /**
     * @var \Gems\Tracker\Respondent
     */
    protected $respondent;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        MenuSnippetHelper $menuHelper,
        TranslatorInterface $translate,
        CurrentUserRepository $currentUserRepository,
        protected readonly Tracker $tracker,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $menuHelper, $translate);

        $this->currentUser = $currentUserRepository->getCurrentUser();
        $this->onEmpty = $this->_('No summary available');
    }

    /*
    public function addBrowseTableColumns(TableBridge $bridge, DataReaderInterface $dataModel)
    {
        parent::addBrowseTableColumns($bridge, $dataModel);
        
        $showMenuItems = $this->getShowUrls($bridge, []);

//        foreach ($showMenuItems as $menuItem) {
//            $link = $menuItem->toActionLinkLower($this->request, $bridge);
//            // $link->target = 'inline';
//            $link->appendAttrib('class', 'inline-answers');
//            $bridge->addItemLink($link);
//        }
    } //*/

    /**
     * Creates the model
     *
     * @return DataReaderInterface
     */
    protected function createModel(): DataReaderInterface
    {
        if (!$this->model instanceof \Gems\Tracker\Model\StandardTokenModel) {
            $model = $this->tracker->getTokenModel();

            $metaModel = $model->getMetaModel();
            $metaModel->set('gto_id_token', [
                'label' => $this->_('Summary'),
                'formatFunction' => [$this, 'getData'],
                ]);
            $metaModel->set('gsu_survey_name', [
                'label' => $this->_('Survey'),
                ]);
            if (!$metaModel->has('forgroup')) {
                $model->addColumn('gems__groups.ggp_name', 'forgroup');
            }
            $metaModel->set('forgroup', [
                'label' => $this->_('Filler'),
                ]);
            $metaModel->setKeys(['gr2o_patient_nr', 'gto_id_organization']);

            $this->model = $model;
        }

        return $this->model;
    }

    public function getHtmlOutput()
    {
        // Make sure we can use jQuery

        $this->columns[] = array('gto_completion_time');
        $this->columns[] = array('gsu_survey_name');
        $this->columns[] = array('forgroup');
        $this->columns[] = array('gto_id_token');

        $html = Html::div(['id' => 'overviewResult']);
        if($roundDescription = $this->requestInfo->getParam('rn')) {
            $html->h2($roundDescription);
        }
        $html->append(parent::getHtmlOutput());

        return $html;
    }

    public function getData($tokenId) {
        try {
            $token = $this->tracker->getToken($tokenId);
            $responses = $token->getRawAnswers();
            $scores = array();
            $questions = $token->getSurvey()->getQuestionList($this->currentUser->getLocale());
            foreach($responses as $key=>$value) {
                if (strtoupper(substr($key,0,5)) == 'SCORE') {
                    if (empty($value)) {
                        $value = $this->_('n/a');
                    }
                    if (!array_key_exists($key, $questions)) {
                        $scores[$key] = $value;
                    } else {                        
                        $scores[$questions[$key]] = $value;
                    }
                }

            }
            if (!empty($scores)) {
                $repeater = new RepeatableByKeyValue($scores);
                $div      = Html::create('div')->setRepeater($repeater)->setAttrib('class', 'row overviewtable');
                // @phpstan-ignore property.notFound
                $div->div($repeater->key, array('class' => 'col-md-6'))->setOnEmpty(Html::raw('empty'));
                // @phpstan-ignore property.notFound
                $div->div($repeater->value, array('class' => 'col-md-6', 'renderWithoutContent'=>false));
                return $div;
            } else {
                return Html::create('div', array('class'=>'row'))->div($this->_('No summary available'), array('class'=>'col-md-12'));
            }
        } catch (\Exception $exc) {
            return null;
        }
    }
    
    /**
     * Fix for forward slash in round description 
     * 
     * The round description can contain a / that is interpreted incorrect, so 
     * in TrafficLightTokenSnippet we encode it to the html entity first. This method does the reverse.
     * 
     * @see \Gems\Snippets\Respondent\TrafficLightTokenSnippet
     * @param \MUtil\Model\ModelAbstract $model
     * /
    public function processFilterAndSort(\MUtil\Model\ModelAbstract $model)
    {
        // 
        $roundDecription  = $this->requestInfo->getParam('gto_round_description');
        if (!is_null($roundDecription)) {
            $roundDecription = html_entity_decode(urldecode($roundDecription));
            // $this->request->setParam('gto_round_description', $roundDecription);
        }
        // parent::processFilterAndSort($model);
    } // */

}
