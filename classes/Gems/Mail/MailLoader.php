<?php

/**
 * Copyright (c) 2013, Erasmus MC
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
 *
 * @package    Gems
 * @subpackage Mail
 * @author     Jasper van Gestel <jappie@dse.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $id MailLoader.php
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Mail
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Mail_MailLoader extends Gems_Loader_LoaderAbstract
{
    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Allows sub classes of Gems_Loader_LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected $cascade = 'Mail';

    /**
     * 
     * @var array Define the mail target options
     */
    protected $mailTargets = array(
        'staff' => 'Staff',
        'respondent' => 'Respondent',
        'token' => 'Token',
        'staffPassword' => 'Password reset',
    );

    /**
     * Return the mail elements helper class
     * @return Gems_Mail_MailElements 
     */
    public function getMailElements()
    {
        return $this->_loadClass('MailElements');
    }

    /**
     * Get the correct mailer class from the given target
     * @param  [type] $target      mailtarget (lowercase)
     * @param  array  $identifiers the identifiers needed for the specific mailtargets
     * @return Mailer class
     */
    public function getMailer($target = null, $id = false, $orgId = false) 
    {   

        if(isset($this->mailTargets[$target])) {
            $target = ucfirst($target);
            return $this->_loadClass($target.'Mailer', true, array($id, $orgId));
        } else {
            return false;
        }
    }

    /**
     * Get the possible mailtargets
     * @return Array  mailtargets
     */
    public function getMailTargets()
    {
        return $this->mailTargets;
    }

    /**
     * Get the form for mailtemplates
     * @param  string $target mailtarget
     * @return [type]          MailTemplateForm
     */
    public function getMailTemplateForm($target=false)
    {
        return $this->_loadClass('MailTemplateForm', true, array($target));
    }

    /**
     * Get default mailform
     * @return Gems_Mail_MailForm
     */
    public function getMailForm()
    {
        return $this->_loadClass('MailForm');
    }

}