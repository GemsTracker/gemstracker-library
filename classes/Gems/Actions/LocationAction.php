<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Actions;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class LocationAction extends \Gems\Controller\ModelSnippetActionAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterParameters = array(
        'columns'     => 'getBrowseColumns',
        'extraSort'   => array('glo_name' => SORT_ASC),
        );

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public $cacheTags = array('location', 'locations');

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showParameters = array(
        'calSearchFilter' => 'getShowFilter',
        'caption'         => 'getShowCaption',
        'onEmpty'         => 'getShowOnEmpty',
        );

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showSnippets = array(
        'Generic\\ContentTitleSnippet',
        'ModelItemTableSnippetGeneric',
        'Agenda\\CalendarTableSnippet',
        );

    /**
     *
     * @var \Gems\Util
     */
    public $util;

    /**
     * Cleanup appointments
     */
    public function cleanupAction()
    {
        $params = $this->_processParameters($this->showParameters);
        $params['contentTitle'] = $this->_('Clean up existing appointments?');
        $params['filterOn']     = 'gap_id_location';
        $params['filterWhen']   = 'glo_filter';

        $snippets = array(
            'Generic\\ContentTitleSnippet',
            'Agenda\\AppointmentCleanupSnippet',
            'Agenda\\CalendarTableSnippet',
            );

        $this->addSnippets($snippets, $params);
    }

    /**
     * Set column usage to use for the browser.
     *
     * Must be an array of arrays containing the input for TableBridge->setMultisort()
     *
     * @return array or false
     */
    public function getBrowseColumns()
    {
        // Newline placeholder
        $br = \MUtil\Html::create('br');
        $sp = \MUtil\Html::raw(' ');

        $columns[10] = array('glo_name', $br, 'glo_organizations');
        $columns[20] = array('glo_url', $br, 'glo_url_route');
        $columns[30] = array('glo_address_1', $br, 'glo_zipcode', \MUtil\Html::raw('&nbsp;&nbsp;'), 'glo_city');
        $columns[40] = array(\MUtil\Html::raw('&#9743; '), 'glo_phone_1', $br, 'glo_filter', $sp, 'glo_match_to');

        return $columns;
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
    protected function createModel($detailed, $action)
    {
        $model = new \MUtil\Model\TableModel('gems__locations');
        $yesNo = $this->util->getTranslated()->getYesNo();

        \Gems\Model::setChangeFieldsByPrefix($model, 'glo');

        $model->setDeleteValues('glo_active', 0);

        $model->set('glo_name',                    'label', $this->_('Location'),
                'required', true
                );

        $model->set('glo_organizations', 'label', $this->_('Organizations'),
                'description', $this->_('Checked organizations see this organizations respondents.'),
                'elementClass', 'MultiCheckbox',
                'multiOptions', $this->util->getDbLookup()->getOrganizations(),
                'noSort', true
                );
        $tp = new \MUtil\Model\Type\ConcatenatedRow(':', ', ');
        $tp->apply($model, 'glo_organizations');

        $model->setIfExists('glo_match_to',        'label', $this->_('Import matches'),
                'description', $this->_("Split multiple import matches using '|'.")
                );

        $model->setIfExists('glo_code',        'label', $this->_('Location code'),
                'size', 10,
                'description', $this->_('Optional code name to link the location to program code.'));

        $model->setIfExists('glo_url',         'label', $this->_('Location url'),
                'description', $this->_('Complete url for location: http://www.domain.etc'),
                'validator', 'Url');
        $model->setIfExists('glo_url_route',   'label', $this->_('Location route url'),
                'description', $this->_('Complete url for route to location: http://www.domain.etc'),
                'validator', 'Url');


        $model->setIfExists('glo_address_1',   'label', $this->_('Street'));
        $model->setIfExists('glo_address_2',   'label', ' ');

        $model->setIfExists('glo_zipcode',     'label', $this->_('Zipcode'),
                'size', 7,
                'description', $this->_('E.g.: 0000 AA'),
                'filter', new \Gems\Filter\DutchZipcode()
                );

        $model->setIfExists('glo_city',        'label', $this->_('City'));
        $model->setIfExists('glo_region',      'label', $this->_('Region'));
        $model->setIfExists('glo_iso_country', 'label', $this->_('Country'),
                'multiOptions', $this->util->getLocalized()->getCountries());

        $model->setIfExists('glo_phone_1',     'label', $this->_('Phone'));
        $model->setIfExists('glo_phone_2',     'label', $this->_('Phone 2'));
        $model->setIfExists('glo_phone_3',     'label', $this->_('Phone 3'));
        $model->setIfExists('glo_phone_4',     'label', $this->_('Phone 4'));

        $model->setIfExists('glo_active',      'label', $this->_('Active'),
                'description', $this->_('Inactive means assignable only through automatich processes.'),
                'elementClass', 'Checkbox',
                'multiOptions', $yesNo
                );
        $model->setIfExists('glo_filter',      'label', $this->_('Filter'),
                'description', $this->_('When checked appointments with these locations are not imported.'),
                'elementClass', 'Checkbox',
                'multiOptions', $yesNo
                );

        $model->addColumn("CASE WHEN glo_active = 1 THEN '' ELSE 'deleted' END", 'row_class');

        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Locations');
    }

    /**
     *
     * @return type
     */
    public function getShowCaption()
    {
        return $this->_('Example appointments');
    }

    /**
     *
     * @return type
     */
    public function getShowOnEmpty()
    {
        return $this->_('No example appointments found');

    }
    /**
     * Get an agenda filter for the current shown item
     *
     * @return array
     */
    public function getShowFilter()
    {
        return array(
            \MUtil\Model::SORT_DESC_PARAM => 'gap_admission_time',
            'gap_id_location' => $this->_getIdParam(),
            'limit' => 10,
            );
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('location', 'locations', $count);
    }

    /**
     * Action for showing a browse page
     */
    public function indexAction()
    {
        parent::indexAction();

        $this->html->pInfo($this->_('A location is a combination of a geographic location and an organization.
 Two organizations sharing a geographic location still need a location item for each organization.'));
    }
}
