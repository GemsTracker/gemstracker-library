<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Overview;

use Gems\Html;
use Gems\Handlers\ModelSnippetLegacyHandlerAbstract;
use Gems\Legacy\CurrentUserRepository;
use Gems\Repository\RespondentRepository;
use Gems\Util\ReceptionCodeLibrary;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 * Action for consent overview
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class ConsentPlanHandler extends ModelSnippetLegacyHandlerAbstract
{
    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     * The parameters used for the index action minus those in autofilter.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $indexParameters = [
        'addCurrentSiblings' => false,
        ];

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStartSnippets = ['Generic\\ContentTitleSnippet'];

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
        'showMenu'      => false,
        'sortParamAsc'  => 'asrt',
        'sortParamDesc' => 'dsrt',
        ];

    /**
     * The snippets used for the show action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $showSnippets = [
        'ModelTableSnippet',
        'Generic\\CurrentSiblingsButtonRowSnippet'
        ];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        CurrentUserRepository $currentUserRepository,
        protected \Zend_Db_Adapter_Abstract $db,
        protected ReceptionCodeLibrary $receptionCodeLibrary,
        protected RespondentRepository $respondentRepository,
    ) {
        parent::__construct($responder, $translate);
        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

    /**
     * @inheritdoc 
     */
    protected function createModel($detailed, $action): DataReaderInterface
    {
        // Export all
        if ('export' === $action) {
            $detailed = true;
        }

        $fixed = ['gr2o_id_organization'];
        $fields = array_combine($fixed, $fixed);
        if ($detailed) {
            $year  = $this->_('Year');
            $month = $this->_('Month');
            $fields[$year]  = new \Zend_Db_Expr("YEAR(gr2o_created)");
            $fields[$month] = new \Zend_Db_Expr("MONTH(gr2o_created)");
        }

        $consents = $this->respondentRepository->getRespondentConsents();
        $deleteds = $this->receptionCodeLibrary->getRespondentDeletionCodes();
        $sql      = "SUM(CASE WHEN grc_success = 1 AND gr2o_consent = '%s' THEN 1 ELSE 0 END)";
        foreach ($consents as $consent => $translated) {
            $fields[$translated] = new \Zend_Db_Expr(sprintf($sql, $consent));
        }
        $fields[$this->_('Total OK')] = new \Zend_Db_Expr("SUM(CASE WHEN grc_success = 1 THEN 1 ELSE 0 END)");

        $sql      = "SUM(CASE WHEN gr2o_reception_code = '%s' THEN 1 ELSE 0 END)";
        foreach ($deleteds as $code => $translated) {
            $fields[$translated] = new \Zend_Db_Expr(sprintf($sql, $code));
        }
        $fields[$this->_('Dropped')] = new \Zend_Db_Expr("SUM(CASE WHEN grc_success = 0 THEN 1 ELSE 0 END)");
        $fields[$this->_('Total')]   = new \Zend_Db_Expr("COUNT(*)");

        $select = $this->db->select();

        $select->from('gems__respondent2org', $fields)
                ->joinInner('gems__reception_codes', 'gr2o_reception_code = grc_id_reception_code', array())
                ->joinInner(
                        'gems__organizations',
                        'gr2o_id_organization = gor_id_organization',
                        array('gor_name', 'gor_id_organization')
                        );

        $select->group(array('gor_name', 'gor_id_organization'));
        if ($detailed) {
            $select->group(array($fields[$year], $fields[$month]));
        }

        $model = new \MUtil\Model\SelectModel($select, 'consent-plan');
        $model->setKeys(array('gor_id_organization'));
        $model->resetOrder();
        $model->set('gor_name', 'label', $this->_('Organization'));
        foreach ($fields as $field => $expr) {
            if ($field != $expr) {
                $model->set($field, 'label', $field,
                            'tdClass', 'rightAlign',
                            'thClass', 'rightAlign');
            }
        }
        foreach ($deleteds as $code => $translated) {
            $model->set($translated,
                    'tdClass', 'rightAlign smallTime',
                    'thClass', 'rightAlign smallTime');
        }
        foreach (array($this->_('Total OK'), $this->_('Dropped'), $this->_('Total')) as $name) {
            $model->set($name, 'itemDisplay', Html::create('strong'),
                    'tableHeaderDisplay', Html::create('em'),
                    'tdClass', 'rightAlign selectedColumn',
                    'thClass', 'rightAlign selectedColumn'
                    );
        }

        if ($detailed) {
            // TODO: show month description
            // $model->set($month, 'formatFunction', );
        }

        // Only show organizations the user is allowed to see
        $allowed = $this->currentUser->getAllowedOrganizations();
        $model->setFilter(array('gr2o_id_organization'=>array_keys($allowed)));

        // \MUtil\Model::$verbose = true;

        return $model;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1): string
    {
        return $this->_('consent per organization');
    }

}
