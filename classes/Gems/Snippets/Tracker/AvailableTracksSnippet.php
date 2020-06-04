<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: AvailableTracksSnippet.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Snippets\Tracker;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 1-mei-2015 16:05:45
 */
class AvailableTracksSnippet extends \Gems_Snippets_ModelTableSnippetAbstract
{
    /**
     * Set a fixed model filter.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedFilter = array(
        'gtr_active' => 1,
        'gtr_date_start <= CURRENT_DATE',
        '(gtr_date_until IS NULL OR gtr_date_until >= CURRENT_DATE)',
        );

    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = array('gtr_track_name' => SORT_ASC, 'gtr_date_start' => SORT_ASC);

    /**
     * Sets pagination on or off.
     *
     * @var boolean
     */
    public $browse = false;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * The respondent
     *
     * @var \Gems_Tracker_Respondent
     */
    protected $respondent;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $orgId = $this->respondent->getOrganizationId();

        // These values are set for the generic table snippet and
        // should be reset for this snippet
        $this->browse          = false;
        $this->extraFilter     = array("gtr_organizations LIKE '%|$orgId|%'");
        $this->menuEditActions = 'view';
        $this->menuShowActions = 'create';
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        $translated = $this->util->getTranslated();

        $model = new \MUtil_Model_TableModel('gems__tracks');

        $model->set('gtr_track_name',    'label', $this->_('Track'));
        $model->set('gtr_survey_rounds', 'label', $this->_('Survey #'));
        $model->set('gtr_date_start',    'label', $this->_('From'),
                'dateFormat', $translated->formatDate,
                'tdClass', 'date'
                );
        $model->set('gtr_date_until',    'label', $this->_('Until'),
                'dateFormat', $translated->formatDateForever,
                'tdClass', 'date'
                );

        $this->loader->getModels()->addDatabaseTranslations($model);

        return $model;
    }
}
