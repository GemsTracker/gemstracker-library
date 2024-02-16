<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets;


use Gems\Html;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model\MetaModelLoader;
use Gems\Repository\TokenRepository;
use Gems\Tracker;
use Gems\Tracker\Model\TokenModel;
use Gems\Tracker\Model\StandardTokenModel;
use Gems\User\Mask\MaskRepository;
use Gems\User\User;
use MUtil\Model\Bridge\TableBridgeAbstract;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelBridge\TableBridge;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * Extra code for displaying token models.
 *
 * Adds columns to the model and adds extra logic for calc_used_date sorting.
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
class TokenModelSnippetAbstract extends ModelTableSnippetAbstract
{
    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'browser table compliance';

    /**
     * @var User
     */
    protected User $currentUser;

    /**
     * A model, not necessarily the token model
     *
     * @var DataReaderInterface
     */
    protected $model;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        MenuSnippetHelper $menuHelper,
        TranslatorInterface $translate,
        CurrentUserRepository $currentUserRepository,
        protected readonly MaskRepository $maskRepository,
        protected readonly MetaModelLoader $metaModelLoader,
        protected readonly Tracker $tracker,
        protected readonly TokenRepository $tokenRepository,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $menuHelper, $translate);

        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

    /**
     *
     * @param TableBridge $bridge
     */
    protected function addActionLinks(TableBridge $bridge)
    {
        $this->addLinks($bridge, $this->getActionLinks($bridge));
    }

    /**
     * @param TableBridge $bridge
     * @param array $links
     * @return void
     */
    protected function addLinks(TableBridge $bridge, array $links)
    {
        $links = array_filter($links);
        if ($links) {
            $bridge->getTable()->addColumn(Html::create('spaced', $links));
        }
    }

    /**
     *
     * @param TableBridge $bridge
     */
    protected function addTokenLinks(TableBridge $bridge)
    {
        $this->addLinks($bridge, $this->getTokenLinks($bridge));
    }

    /**
     * Creates the model
     *
     * @return DataReaderInterface
     */
    protected function createModel(): DataReaderInterface
    {
        if ($this->model instanceof StandardTokenModel) {
            $model = $this->model;
        } elseif ($this->model instanceof TokenModel) {
            $model = $this->model;
        } else {
            if (TokenModel::$useTokenModel) {
                $model = $this->metaModelLoader->createModel(TokenModel::class);
            } else {
                $model = $this->tracker->getTokenModel();
            }
        }
        if (method_exists($model, 'addColumn')) {
            $model->addColumn(
                'CASE WHEN gto_completion_time IS NULL THEN gto_valid_from ELSE gto_completion_time END',
                'calc_used_date',
                'gto_valid_from');
            $model->addColumn(
                'CASE WHEN gto_completion_time IS NULL THEN gto_valid_from ELSE NULL END',
                'calc_valid_from',
                'gto_valid_from');
            $model->addColumn(
                'CASE WHEN gto_completion_time IS NULL AND grc_success = 1 AND gto_valid_from <= CURRENT_TIMESTAMP AND gto_completion_time IS NULL AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP) THEN gto_id_token ELSE NULL END',
                'calc_id_token',
                'gto_id_token');
            $model->addColumn(
                'CASE WHEN gto_completion_time IS NULL AND grc_success = 1 AND gto_valid_from <= CURRENT_TIMESTAMP AND gto_completion_time IS NULL AND gto_valid_until < CURRENT_TIMESTAMP THEN 1 ELSE 0 END',
                'was_missed');
        }
        return $model;
    }

    /**
     *
     * @param TableBridge $bridge
     */
    protected function getActionLinks(TableBridge $bridge): array
    {
        $actionLinks[] = $this->tokenRepository->getTokenAskLinkForBridge($bridge, $this->menuHelper);
        $actionLinks[] = $this->tokenRepository->getTokenAnswerLinkForBridge($bridge, $this->menuHelper);

        return $actionLinks;
    }

    /**
     *
     * @param TableBridge $bridge
     */
    protected function getTokenLinks(TableBridge $bridge): array
    {
        $tokenLinks[] = $this->tokenRepository->getTokenShowLinkForBridge($bridge, $this->menuHelper);

        return $tokenLinks;
    }

    /**
     * calc_used_date has special sort, see bugs 108 and 127
     *
     * @param DataReaderInterface $model
     */
    protected function sortCalcDateCheck(DataReaderInterface $model)
    {
        $sort = $model->getSort();

        if (isset($sort['calc_used_date'])) {
            $add        = true;
            $resultSort = array();

            foreach ($sort as $key => $asc) {
                if ('calc_used_date' === $key) {
                    if ($add) {
                        $resultSort['is_completed']        = $asc;
                        $resultSort['gto_completion_time'] = $asc == SORT_ASC ? SORT_DESC : SORT_ASC;
                        $resultSort['calc_valid_from']     = $asc;
                        $add = false; // We can add this only once
                    }
                } else {
                    $resultSort[$key] = $asc;
                }
            }

            if (! $add) {
                $model->setSort($resultSort);
            }
        }
    }
}
