<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Token;

/**
 * Snippet for showing the all tokens for a single respondent.
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class RespondentTokenSnippet extends \Gems\Snippets\TokenModelSnippetAbstract
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = array(
            'calc_used_date'  => SORT_ASC,
            'gtr_track_name'  => SORT_ASC,
            'gto_round_order' => SORT_ASC,
            'gto_created'     => SORT_ASC);

    /**
     * Sets pagination on or off.
     *
     * @var boolean
     */
    public $browse = true;

    /**
     * When true: show tokens for all organizations, false: only current organization, array => those organizations
     *
     * @var mixed boolean or array
     */
    protected $forOtherOrgs = false;

    /**
     * The RESPONDENT model, not the token model
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $model;

    /**
     * Required
     *
     * @var \Gems\Tracker\Respondent
     */
    protected $respondent;

    /**
     * Require
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil\Model\Bridge\TableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(\MUtil\Model\Bridge\TableBridge $bridge, \MUtil\Model\ModelAbstract $model)
    {
        // \MUtil\Model::$verbose = true;
        //
        // Initiate data retrieval for stuff needed by links
        $bridge->gr2o_patient_nr;
        $bridge->gr2o_id_organization;
        $bridge->gr2t_id_respondent_track;

        $HTML = \MUtil\Html::create();

        $model->set('gto_round_description', 'tableDisplay', 'smallData');
        $model->set('gr2t_track_info', 'tableDisplay', 'smallData');

        $roundIcon[] = \MUtil\Lazy::iif($bridge->gto_icon_file, \MUtil\Html::create('img', array('src' => $bridge->gto_icon_file, 'class' => 'icon')),
                \MUtil\Lazy::iif($bridge->gro_icon_file, \MUtil\Html::create('img', array('src' => $bridge->gro_icon_file, 'class' => 'icon'))));

        $bridge->td($this->util->getTokenData()->getTokenStatusLinkForBridge($bridge, false));

        if ($menuItem = $this->findMenuItem('track', 'show-track')) {
            $href = $menuItem->toHRefAttribute($this->request, $bridge);
            $track1 = $HTML->if($bridge->gtr_track_name, $HTML->a($href, $bridge->gtr_track_name));
        } else {
            $track1 = $bridge->gtr_track_name;
        }
        $track = array($track1, $bridge->createSortLink('gtr_track_name'));

        $bridge->addMultiSort($track, 'gr2t_track_info');
        $bridge->addMultiSort('gsu_survey_name', 'gto_round_description', $roundIcon);
        $bridge->addSortable('ggp_name');
        $bridge->addSortable('calc_used_date', null, $HTML->if($bridge->is_completed, 'disabled date', 'enabled date'));
        $bridge->addSortable('gto_changed');
        $bridge->addSortable('assigned_by', $this->_('Assigned by'));
        $project = \Gems\Escort::getInstance()->project;

        // If we are allowed to see the result of the survey, show them
        if ($this->currentUser->hasPrivilege('pr.respondent.result') &&
                (! $this->currentUser->isFieldMaskedWhole('gto_result'))) {
            $bridge->addSortable('gto_result', $this->_('Score'), 'date');
        }

        $bridge->useRowHref = false;

        $this->addActionLinks($bridge);
        $this->addTokenLinks($bridge);
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
    public function hasHtmlOutput()
    {
        return $this->respondent && $this->request && parent::hasHtmlOutput();
    }

    /**
     * Overrule to implement snippet specific filtering and sorting.
     *
     * @param \MUtil\Model\ModelAbstract $model
     */
    protected function processFilterAndSort(\MUtil\Model\ModelAbstract $model)
    {
        $filter['gto_id_respondent']   = $this->respondent->getId();
        if (is_array($this->forOtherOrgs)) {
            $filter['gto_id_organization'] = $this->forOtherOrgs;
        } elseif (true !== $this->forOtherOrgs) {
            $filter['gto_id_organization'] = $this->respondent->getOrganizationId();
        }

        // Filter for valid track reception codes
        $filter[] = 'gr2t_reception_code IN (SELECT grc_id_reception_code FROM gems__reception_codes WHERE grc_success = 1)';
        $filter['grc_success'] = 1;
        // Active round
        // or
        // no round
        // or
        // token is success and completed
        $filter[] = 'gro_active = 1 OR gro_active IS NULL OR (grc_success=1 AND gto_completion_time IS NOT NULL)';
        $filter['gsu_active']  = 1;

        // NOTE! $this->model does not need to be the token model, but $model is a token model
        $tabFilter = $this->model->getMeta('tab_filter');
        if ($tabFilter) {
            $model->addFilter($tabFilter);
        }

        $model->addFilter($filter);

        // \MUtil\EchoOut\EchoOut::track($model->getFilter());

        $this->processSortOnly($model);
    }
}
