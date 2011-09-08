<?php

/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: MailServerAction.php 478 2011-09-07 11:20:36Z mjong $
 */

/**
 *
 * @package Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Default_MailServerAction extends Gems_Controller_BrowseEditAction
{
    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * $return MUtil_Model_ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $model = new MUtil_Model_TableModel('gems__mail_servers');

        Gems_Model::setChangeFieldsByPrefix($model, 'gms');

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
                    Gems_Email_TemplateMailer::MAIL_NO_ENCRYPT => $this->_('None'),
                    Gems_Email_TemplateMailer::MAIL_SSL => $this->_('SSL'),
                    Gems_Email_TemplateMailer::MAIL_TLS => $this->_('TLS')));
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
            $model->setSaveWhenNotNull('gms_password');
}

        return $model;
    }

    public function getTopic($count = 1)
    {
        return $this->plural('email server', 'email servers', $count);
    }

    public function getTopicTitle()
    {
        return $this->_('Email servers');
    }
}
