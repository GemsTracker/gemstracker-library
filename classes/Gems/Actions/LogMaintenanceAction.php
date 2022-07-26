<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Actions;

use Gems\Util\Translated;

/**
 * The maintenace screen for the action log
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class LogMaintenanceAction extends \Gems\Controller\ModelSnippetActionAbstract
{
    /**
     * The parameters used for the autofilter action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $autofilterParameters = [
        'extraSort' => ['gls_name' => SORT_ASC],
    ];

    /**
     * The parameters used for the create and edit actions.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $createEditParameters = [
        'cacheTags' => ['accesslog_actions'],
    ];

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = ['Generic\\ContentTitleSnippet', 'Log\\LogMaintenanceSearchSnippet'];

    /**
     * @var Translated
     */
    public $translatedUtil;

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
        $model = new \Gems\Model\JoinModel('log_maint', 'gems__log_setup', 'gls', true);
        $model->set('gls_name', 'label', $this->_('Action'),
                'elementClass', ('create' == $action) ? 'Text' : 'Exhibitor',
                'validators[unique]', $model->createUniqueValidator('gls_name'));

        $model->set('gls_when_no_user', 'label', $this->_('Log when no user'),
                'description', $this->_('Always log this action, even when no one is logged in.'),
                'elementClass', 'CheckBox',
                'multiOptions', $this->translatedUtil->getYesNo()
                );

        $model->set('gls_on_action', 'label', $this->_('Log view'),
                'description', $this->_('Always log when viewed / opened.'),
                'elementClass', 'CheckBox',
                'multiOptions', $this->translatedUtil->getYesNo()
                );

        $model->set('gls_on_post', 'label', $this->_('Log change tries'),
                'description', $this->_('Log when trying to change the data.'),
                'elementClass', 'CheckBox',
                'multiOptions', $this->translatedUtil->getYesNo()
                );

        $model->set('gls_on_change', 'label', $this->_('Log data change'),
                'description', $this->_('Log when data changes.'),
                'elementClass', 'CheckBox',
                'multiOptions', $this->translatedUtil->getYesNo()
                );

        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Logging Setup');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->_('Log action');
    }
}