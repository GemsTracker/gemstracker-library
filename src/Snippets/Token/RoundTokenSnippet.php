<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Token;

use Gems\Html;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model;
use Gems\Model\MetaModelLoader;
use Gems\Model\Type\GemsDateTimeType;
use Gems\Model\Type\GemsDateType;
use Gems\Repository\TokenRepository;
use Gems\Tracker;
use Gems\User\Mask\MaskRepository;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Late\Late;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelBridge\TableBridge;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.1
 */
class RoundTokenSnippet extends RespondentTokenSnippet
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = array(
            'gto_round_order' => SORT_ASC,
            'gto_created'     => SORT_ASC,
        );

    /**
     * Sets pagination on or off.
     *
     * @var boolean
     */
    public $browse = false;

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
        protected readonly array $config,
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
        // Initiate data retrieval for stuff needed by links
        $bridge->getFormatted('gr2o_patient_nr');
        $bridge->getFormatted('gr2o_id_organization');
        $bridge->getFormatted('gr2t_id_respondent_track');

        $HTML = Html::create();

        $iconFile = $bridge->getFormatted('gto_icon_file');
        $roundIcon[] = Late::iif($iconFile, Html::create('img', array('src' => $iconFile, 'class' => 'icon')), '');

        $bridge->addMultiSort('gsu_survey_name', $roundIcon);
        $bridge->addSortable('ggp_name');
        $bridge->addSortable('calc_used_date', null, Late::iif($bridge->getFormatted('is_completed'), 'disabled date', 'enabled date'));
        $bridge->addSortable('gto_changed');
        $bridge->addSortable('assigned_by', $this->_('Assigned by'));

        // If we are allowed to see the result of the survey, show them
        if ($this->currentUser->hasPrivilege('pr.respondent.result') &&
                (! $this->maskRepository->isFieldMaskedWhole('gto_result'))) {
            $bridge->addSortable('gto_result', $this->_('Score'), 'date');
        }

        $this->addActionLinks($bridge);
        $this->addTokenLinks($bridge);
    }

    /**
     * Creates the model
     *
     * @return DataReaderInterface
     */
    protected function createModel(): DataReaderInterface
    {
        $model = parent::createModel();
        $metaModel = $model->getMetaModel();

        $metaModel->set('calc_used_date', [
                'tdClass' => 'date',
                'type' => new GemsDateType($this->translate),
                ]);
        $metaModel->set('gto_changed', [
                'tdClass' => 'date',
                'type' => new GemsDateTimeType($this->translate),
                ]);

        return $model;
    }

    /**
     * @inheritdoc
     */
    public function hasHtmlOutput(): bool
    {
        $url   = null;
        $label = $this->_('No valid tokens found');

        if ($this->respondent->getReceptionCode()->isSuccess()) {
            if (isset($this->config['survey']['defaultTrackId'])) {
                $default = $this->config['survey']['defaultTrackId'];
                $track = $this->tracker->getTrackEngine($default);
                if ($track->isUserCreatable()) {
                    $url   = $this->menuHelper->getRouteUrl('respondent.tracks.create', $this->requestInfo->getRequestMatchedParams() + [Model::TRACK_ID => $default]);
                    $label = $this->_('Create new track');
                }
            }
            if (! $url) {
                $url   = $this->menuHelper->getRouteUrl('respondent.tracks.index', $this->requestInfo->getRequestMatchedParams());
                $label = $this->_('Create new track');
            }
        }

        if ($url) {
            $this->onEmpty = Html::create('actionLink', $url, $label);
        } else {
            $this->onEmpty = Html::create('em', $label);
        }

        return parent::hasHtmlOutput();
    }
}
