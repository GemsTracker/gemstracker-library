<?php

/**
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Default_ConsentAction extends \Gems_Controller_ModelSnippetActionAbstract
{
    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public $cacheTags = array('consent', 'consents');

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterParameters = array(
        'extraSort' => array('gco_order' => SORT_ASC, ),
        );

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil_Model_ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $model = new \MUtil_Model_TableModel('gems__consents');
        $model->copyKeys(); // The user can edit the keys.

        $model->set('gco_description', 'label', $this->_('Description'), 'size', '10');

        $model->set('gco_order',       'label', $this->_('Order'), 'size', '10',
            'description', $this->_('Determines order of presentation in interface.'),
            'validator', 'Digits');
        $model->set('gco_code',        'label', $this->_('Consent code'),
            'multiOptions', $this->util->getConsentTypes(),
            'description', $this->_('Internal code, not visible to users, copied with the token information to the source.'));
        if ($detailed) {
            $model->set('gco_description', 'validator', $model->createUniqueValidator('gco_description'));
            $model->set('gco_order',       'validator', $model->createUniqueValidator('gco_order'));
        }

        if ($this->project->multiLocale) {
            $model->set('gco_description', 'description', 'ENGLISH please! Use translation file to translate.');
        }

        \Gems_Model::setChangeFieldsByPrefix($model, 'gco');

        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Respondent informed consent codes');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('respondent consent', 'respondent consents', $count);
    }
}
