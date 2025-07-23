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

use Gems\Html;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model\MetaModelLoader;
use Gems\Repository\OrganizationRepository;
use Gems\Repository\TokenRepository;
use Gems\Snippets\TokenModelSnippetAbstract;
use Gems\Tracker;
use Gems\Tracker\Respondent;
use Gems\User\Mask\MaskRepository;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Late\Late;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Snippets\ModelBridge\TableBridge;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * Snippet for showing the all tokens for a single respondent.
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class RespondentTokenSnippet extends TokenModelSnippetAbstract
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = [
        'calc_used_date'  => SORT_ASC,
        'gtr_track_name'  => SORT_ASC,
        'gto_round_order' => SORT_ASC,
        'gto_created'     => SORT_ASC
    ];

    /**
     * Sets pagination on or off.
     *
     * @var boolean
     */
    public $browse = true;

    /**
     * The RESPONDENT model, not the token model
     *
     * @var DataReaderInterface
     */
    protected $model;

    /**
     * Required
     *
     * @var null|\Gems\Tracker\Respondent
     */
    protected ?Respondent $respondent = null;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        MenuSnippetHelper $menuHelper,
        TranslatorInterface $translate,
        CurrentUserRepository $currentUserRepository,
        MaskRepository $maskRepository,
        MetaModelLoader $metaModelLoader,
        Tracker $tracker,
        TokenRepository $tokenRepository,
        protected readonly OrganizationRepository $organizationRepository,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $menuHelper, $translate, $currentUserRepository, $maskRepository, $metaModelLoader, $tracker, $tokenRepository);
    }

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param TableBridge $bridge
     * @param DataReaderInterface $dataModel
     * @return void
     */
    protected function addBrowseTableColumns(TableBridge $bridge, DataReaderInterface $dataModel)
    {
        $metaModel = $dataModel->getMetaModel();

        // \MUtil\Model::$verbose = true;
        //
        // Initiate data retrieval for stuff needed by links
        $bridge->gr2o_patient_nr;
        $bridge->gr2o_id_organization;
        $bridge->gr2t_id_respondent_track;

        $HTML = Html::create();

        $metaModel->set('gto_round_description', 'table', 'small');
        $metaModel->set('gr2t_track_info', 'table', 'small');

        $roundIcon[] = Late::iif($bridge->gto_icon_file, Html::create('img', array('src' => $bridge->gto_icon_file, 'class' => 'icon')),
                Late::iif($bridge->gro_icon_file, Html::create('img', array('src' => $bridge->gro_icon_file, 'class' => 'icon'))));

        // $bridge->td($this->util->getTokenData()->getTokenStatusLinkForBridge($bridge, false));

//        if (false && $menuItem = $this->findMenuItem('track', 'show-track')) {
//            $href = $menuItem->toHRefAttribute($this->request, $bridge);
//            $track1 = $HTML->if($bridge->gtr_track_name, $HTML->a($href, $bridge->gtr_track_name));
//        } else {
            $track1 = $bridge->gtr_track_name;
//        }
        $track = array($track1, $bridge->createSortLink('gtr_track_name'));

        $bridge->addMultiSort($track, 'gr2t_track_info');
        $bridge->addMultiSort('gsu_survey_name', 'gto_round_description', $roundIcon);
        $bridge->addSortable('ggp_name');
        $bridge->addSortable('calc_used_date', null, $HTML->if($bridge->is_completed, 'disabled date', 'enabled date'));
        $bridge->addSortable('gto_changed');
        $bridge->addSortable('assigned_by', $this->_('Assigned by'));
        // $project = \Gems\Escort::getInstance()->project;

        // If we are allowed to see the result of the survey, show them
//        if ($this->currentUser->hasPrivilege('pr.respondent.result') &&
//                (! $this->maskRepository->isFieldMaskedWhole('gto_result'))) {
            $bridge->addSortable('gto_result', $this->_('Score'), 'date');
//        }

        $this->addActionLinks($bridge);
        $this->addTokenLinks($bridge);
    }

    /**
     * @inheritdoc
     */
    public function hasHtmlOutput(): bool
    {
        return $this->respondent && parent::hasHtmlOutput();
    }

    public function getFilter(MetaModelInterface $metaModel): array
    {
        $respondentId = $this->respondent->getId();
        $filter['gto_id_respondent']   = $respondentId;
        $filter['gto_id_organization'] = $this->organizationRepository->getExtraTokenOrgsFor($respondentId, $this->respondent->getOrganizationId());;

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

        // NOTE! $this->model does not need to be the token model, but $metaModel is of a token model
        // so we need to access the right metamodel
        $tabFilter = $this->model->getMetaModel()->getMeta('tab_filter');
        if ($tabFilter) {
            $this->extraFilter = $tabFilter;
        }

        $this->extraFilter += $filter;

        return parent::getFilter($metaModel);
    }
}
