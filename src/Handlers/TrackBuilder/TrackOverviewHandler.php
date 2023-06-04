<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\TrackBuilder;

use Gems\Handlers\ModelSnippetLegacyHandlerAbstract;
use Gems\Html;
use Gems\Repository\OrganizationRepository;
use MUtil\Model\ModelAbstract;
use MUtil\Model\SelectModel;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 * Action for consent overview
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.4
 */
class TrackOverviewHandler extends ModelSnippetLegacyHandlerAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $autofilterSnippets = ['Tracker\\Overview\\TableSnippet'];

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    //protected $indexStartSnippets = array('Generic\\ContentTitleSnippet');

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
        'browse'        => true,
        'onEmpty'       => 'getOnEmptyText',
        'showMenu'      => true,
        'sortParamAsc'  => 'asrt',
        'sortParamDesc' => 'dsrt',
    ];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        protected OrganizationRepository $organizationRepository,
        protected \Zend_Db_Adapter_Abstract $db,
    )
    {
        parent::__construct($responder, $translate);
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
    protected function createModel(bool $detailed, string $action): ModelAbstract
    {
        $fields = [];
        // Export all
        if ('export' === $action) {
            $detailed = true;
        }

        $organizations = $this->organizationRepository->getOrganizations();


        $fields[] = 'gtr_track_name';

        $sql      = "CASE WHEN gtr_organizations LIKE '%%|%s|%%' THEN 1 ELSE 0 END";

        foreach ($organizations as $orgId => $orgName) {
            $fields['O'.$orgId] = new \Zend_Db_Expr(sprintf($sql, $orgId));
        }

        $fields['total'] = new \Zend_Db_Expr("(LENGTH(gtr_organizations) - LENGTH(REPLACE(gtr_organizations, '|', ''))-1)");

        $fields[] = 'gtr_id_track';

        $select = $this->db->select();
        $select->from('gems__tracks', $fields);

        $model = new SelectModel($select, 'track-verview');
        $model->setKeys(array('gtr_id_track'));
        $model->resetOrder();

        $model->set('gtr_track_name', 'label', $this->_('Track name'));

        $model->set('total', 'label', $this->_('Total'));
        $model->setOnTextFilter('total', array($this, 'noTextFilter'));

        foreach ($organizations as $orgId => $orgName) {
            $model->set('O' . $orgId, 'label', $orgName,
                    'tdClass', 'rightAlign',
                    'thClass', 'rightAlign');

            $model->setOnTextFilter('O' . $orgId, array($this, 'noTextFilter'));

            if ($action !== 'export') {
                $model->set('O'. $orgId, 'formatFunction', array($this, 'formatCheckmark'));
            }
        }

         // \MUtil\Model::$verbose = true;

        return $model;
    }

    public function formatCheckmark($value)
    {
        if ($value === 1) {
            return Html::create('span', ['class'=>'checked'])->i(['class' => 'fa fa-check', 'style' => 'color: green;']);
        }
        return null;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic(int $count = 1): string
    {
        return $this->_('track per organization');
    }

    /**
     * Calculated fields can not exist in a where clause.
     *
     * We don't need to search on them with the text filter, so we return
     * an empty array to disable text search.
     *
     * @return array
     */
    public function noTextFilter()
    {
        return [];
    }
}