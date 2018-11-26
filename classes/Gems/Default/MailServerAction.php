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
 * @since      Class available since version 1.4
 */
class Gems_Default_MailServerAction extends \Gems_Controller_ModelSnippetActionAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterParameters = array(
        'extraSort'   => array('gms_from' => SORT_ASC),
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
     * $return \MUtil_Model_ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $model = new \MUtil_Model_TableModel('gems__mail_servers');

        \Gems_Model::setChangeFieldsByPrefix($model, 'gms');

        // Key can be changed by users
        $model->copyKeys();

        $model->set('gms_from',
                'label', $this->_('From address [part]'),
                'size', 30,
                'description', $this->_("E.g.: '%', '%.org' or '%@gemstracker.org' or 'root@gemstracker.org'."));
        $model->set('gms_server', 'label', $this->_('Server'), 'size', 30);
        $model->set('gms_ssl',
                'label', $this->_('Encryption'),
                'required', false,
                'multiOptions', array(
                    \Gems_Mail::MAIL_NO_ENCRYPT => $this->_('None'),
                    \Gems_Mail::MAIL_SSL => $this->_('SSL'),
                    \Gems_Mail::MAIL_TLS => $this->_('TLS')));
        $model->set('gms_port',
                'label', $this->_('Port'),
                'required', true,
                'description', $this->_('Normal values: 25 for TLS and no encryption, 465 for SSL'),
                'validator', 'Digits');
        $model->set('gms_user',   'label', $this->_('User ID'), 'size', 20);

        if ($detailed) {
            $model->set('gms_password',
                    'label', $this->_('Password'),
                    'elementClass', 'Password',
                    'repeatLabel', $this->_('Repeat password'),
                    'description', $this->_('Enter only when changing'));

            $type = new \Gems_Model_Type_EncryptedField($this->project, true);
            $type->apply($model, 'gms_password');
        }

        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Email servers');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('email server', 'email servers', $count);
    }
}
