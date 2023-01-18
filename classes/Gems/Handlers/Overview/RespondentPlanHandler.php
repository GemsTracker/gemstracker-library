<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Overview;

use Gems\Db\ResultFetcher;
use Gems\MenuNew\RouteHelper;
use Gems\Selector\TokenDateSelector;
use Gems\Tracker;
use Gems\Tracker\Model\StandardTokenModel;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class RespondentPlanHandler extends TokenSearchHandlerAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $autofilterSnippets = ['Token\\PlanRespondentSnippet'];

    /**
     * The snippets used for the index action, after those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStopSnippets = [
        'Tracker\\TokenStatusLegenda',
        'Generic\\CurrentSiblingsButtonRowSnippet',
        ];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        Tracker $tracker,
        ResultFetcher $resultFetcher,
        protected RouteHelper $routeHelper,
        protected TokenDateSelector $dateSelector,
    ) {
        parent::__construct($responder, $translate, $tracker, $resultFetcher);
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
     * @return StandardTokenModel
     */
    public function createModel(bool $detailed, string $action): StandardTokenModel
    {
        $model = parent::createModel($detailed, $action);

        $model->set('grs_birthday', 'label', $this->_('Birthday'));
        $model->set('grs_city', 'label', $this->_('City'));

        $model->addColumn("CONCAT(gr2t_completed, '" . $this->_(' of ') . "', gr2t_count)", 'track_progress');
        $model->set('track_progress', 'label', $this->_('Progress'));

        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return string
     */
    public function getIndexTitle(): string
    {
        return $this->_('Respondent planning');
    }
}