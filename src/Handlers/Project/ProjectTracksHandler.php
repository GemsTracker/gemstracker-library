<?php

/**
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Project;

use Gems\Legacy\CurrentUserRepository;
use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\Generic\CurrentButtonRowSnippet;
use Gems\Snippets\ModelDetailTableSnippet;
use Gems\Snippets\Tracker\TrackSurveyOverviewSnippet;
use Gems\User\User;
use Gems\Util\Translated;
use MUtil\Model\ModelAbstract;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 * @package Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class ProjectTracksHandler extends \Gems\Handlers\ModelSnippetLegacyHandlerAbstract
{
    /**
     * The parameters used for the autofilter action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $autofilterParameters = [
        'extraFilter' => [
            'gtr_active' => 1,
            -2 => '(gtr_date_until IS NULL OR gtr_date_until >= CURRENT_DATE) AND gtr_date_start <= CURRENT_DATE'
        ],
        'extraSort' => [
            'gtr_track_name' => SORT_ASC,
        ],
    ];

    protected User $currentUser;
    
    /**
     * The parameters used for the show action
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $showParameters = [
        'showHeader' => true,
        'trackId'    => '_getIdParam',
        'trackData'  => 'getTrackData',
    ];

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected array $showSnippets = [
        ContentTitleSnippet::class,
        ModelDetailTableSnippet::class,
        CurrentButtonRowSnippet::class,
        TrackSurveyOverviewSnippet::class,
    ]; 

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        CurrentUserRepository $currentUserRepository,
        protected Translated $translatedUtil,
    )
    {
        parent::__construct($responder, $translate);
        
        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel($detailed, $action): ModelAbstract
    {
        $model = new \MUtil\Model\TableModel('gems__tracks');

        $model->set('gtr_track_name',    'label', $this->_('Track'));
        $model->set('gtr_survey_rounds', 'label', $this->_('Survey #'));
        $model->set('gtr_date_start',    'label', $this->_('From'),
                'tdClass', 'date'
                );
        $model->set('gtr_date_until',    'label', $this->_('Until'),
                'tdClass', 'date'
                );

        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle(): string
    {
        return $this->_('Active tracks');
    }

    /**
     * Function to allow the creation of search defaults in code
     *
     * @see getSearchFilter()
     *
     * @return array
     */
    public function getSearchDefaults(): array
    {
        if (! $this->defaultSearchData) {
            $orgId = $this->currentUser->getCurrentOrganizationId();
            $this->defaultSearchData[-1] = "gtr_organizations LIKE '%|$orgId|%'";
        }

        return parent::getSearchDefaults();
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return string
     */
    public function getTopic($count = 1): string
    {
        return $this->plural('track', 'tracks', $count);
    }

    /**
     *
     * @return array
     */
    public function getTrackData()
    {
        return $this->getModel()->loadFirst(['gtr_id_track' => $this->requestInfo->getParam(\MUtil\Model::REQUEST_ID)]);
    }
}
