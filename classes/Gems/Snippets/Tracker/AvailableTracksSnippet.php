<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker;

use Gems\MenuNew\MenuSnippetHelper;
use Gems\Model;
use Gems\Snippets\ModelTableSnippetAbstract;
use Gems\Util\Translated;
use MUtil\Model\TableModel;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Snippets\ModelBridge\TableBridge;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 1-mei-2015 16:05:45
 */
class AvailableTracksSnippet extends ModelTableSnippetAbstract
{
    /**
     * Set a fixed model filter.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedFilter = [
        'gtr_active' => 1,
        'gtr_date_start <= CURRENT_DATE',
        '(gtr_date_until IS NULL OR gtr_date_until >= CURRENT_DATE)',
    ];

    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = ['gtr_track_name' => SORT_ASC, 'gtr_date_start' => SORT_ASC];

    /**
     * Sets pagination on or off.
     *
     * @var boolean
     */
    public $browse = false;

    /**
     * The respondent
     *
     * @var \Gems\Tracker\Respondent
     */
    protected $respondent;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        MenuSnippetHelper $menuHelper,
        TranslatorInterface $translate,
        protected Translated $translatedUtil,
        protected Model $modelLoader,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $menuHelper, $translate);

        // $this->respondent loaded as SnippetOption!
        $orgId = $this->respondent->getOrganizationId();

        // These values are set for the generic table snippet and
        // should be reset for this snippet
        $this->browse          = false;
        $this->extraFilter     = ["gtr_organizations LIKE '%|$orgId|%'"];
//        $this->menuEditRoutes = [$this->_('View') => 'view'];
//        $this->menuShowRoutes = [$this->_('Create') => 'create'];
    }

    /**
     * Creates the model
     *
     * @return DataReaderInterface
     */
    protected function createModel(): DataReaderInterface
    {
        $model = new TableModel('gems__tracks');

        $model->set('gtr_track_name',    'label', $this->_('Track'));
        $model->set('gtr_survey_rounds', 'label', $this->_('Survey #'));
        $model->set('gtr_date_start',    'label', $this->_('From'),
                'dateFormat', $this->translatedUtil->formatDate,
                'tdClass', 'date'
                );
        $model->set('gtr_date_until',    'label', $this->_('Until'),
                'dateFormat', $this->translatedUtil->formatDateForever,
                'tdClass', 'date'
                );

        $model->setKeys([
            Model::TRACK_ID => 'gtr_id_track'
        ]);

        $this->modelLoader->addDatabaseTranslations($model);

        return $model;
    }

    protected function getEditUrls(TableBridge $bridge, array $keys): array
    {
        $params[\MUtil\Model::REQUEST_ID] = $bridge->gtr_id_track;

        return $this->menuHelper->getLateRelatedUrls(['project.tracks.show'], $params);
    }

    public function getRouteMaps(MetaModelInterface $metaModel): array
    {
        $output = parent::getRouteMaps($metaModel);
        $output[Model::TRACK_ID] = 'gtr_id_track';
        return $output;
    }

    /**
     * Returns a show menu item, if access is allowed by privileges
     */
    protected function getShowUrls(TableBridge $bridge, array $keys): array
    {
        $params[\MUtil\Model::REQUEST_ID1] = \MUtil\Model::REQUEST_ID1;
        $params[\MUtil\Model::REQUEST_ID2] = \MUtil\Model::REQUEST_ID2;
        $params[Model::TRACK_ID] = $bridge->gtr_id_track;

        return $this->menuHelper->getLateRelatedUrls(['respondent.tracks.create'], $params);
    }
}
