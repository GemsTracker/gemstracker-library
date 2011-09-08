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
 */

/**
 * 
 * @author Matijs de Jong
 * @since 1.0
 * @version 1.1
 * @package Gems
 * @subpackage Default
 */

/**
 * 
 * @author Matijs de Jong
 * @package Gems
 * @subpackage Default
 */
class Gems_Default_ProjectInformationAction  extends Gems_Controller_Action
{
    public $useHtmlView = true;

    protected function _showTable($caption, $data, $nested = false)
    {
        $table = MUtil_Html_TableElement::createArray($data, $caption, $nested);
        $table->class = 'browser';
        $this->html[] = $table;
    }

    public function aclAction()
    {
        $this->html->h2($this->_('Access Control Lists'));
        $this->_showTable($this->_('ACL\'s'), $this->acl->getRoles());
    }

    public function changelogAction()
    {
        $this->html->h2($this->_('Changelog'));

        $log_path = APPLICATION_PATH;
        $log_file = $log_path . '/changelog.txt';

        if ((1 == $this->_getParam(MUtil_Model::REQUEST_ID)) && file_exists($log_file)) {
            unlink($log_file);
        }

        if (file_exists($log_file)) {
            $this->html->pre(trim(file_get_contents($log_file)), array('class' => 'logFile'));
        } else {
            $this->html->pInfo(sprintf($this->_('No changelog found. Place one in %s.'), $log_file));
        }
    }

    public function errorsAction()
    {
        $this->html->h2($this->_('Logged errors'));

        $log_path = GEMS_ROOT_DIR . '/var/logs';
        $log_file = $log_path . '/errors.log';

        if ((1 == $this->_getParam(MUtil_Model::REQUEST_ID)) && file_exists($log_file)) {
            unlink($log_file);
        }

        if (file_exists($log_file)) {
            $buttons = $this->html->buttonDiv();
            $buttons->actionLink(array(MUtil_Model::REQUEST_ID => 1), $this->_('Empty logfile'));

            $this->html->pre(trim(file_get_contents($log_file)), array('class' => 'logFile'));

            $this->html[] = $buttons;
        } else {
            $this->html->pInfo($this->_('No logged errors found.'));
            $this->html->buttonDiv()->actionDisabled($this->_('Empty logfile'));
        }
    }

    public function indexAction()
    {
        $this->html->h2($this->_('Project information'));

        $versions = $this->loader->getVersions();
        
        $data[$this->_('Project name')]            = $this->project->name;
        $data[$this->_('Project version')]         = $versions->getProjectVersion();
        $data[$this->_('Gems version')]            = $versions->getGemsVersion();
        $data[$this->_('Gems build')]              = $versions->getBuild();
        $data[$this->_('Gems project')]            = GEMS_PROJECT_NAME;
        $data[$this->_('Gems web directory')]      = GEMS_ROOT_DIR;
        $data[$this->_('Gems code directory')]     = GEMS_LIBRARY_DIR;
        $data[$this->_('Gems project path')]       = GEMS_PROJECT_PATH;
        $data[$this->_('MUtil version')]           = MUtil_Version::get();
        $data[$this->_('Zend version')]            = Zend_Version::VERSION;
        $data[$this->_('Application environment')] = APPLICATION_ENV;
        $data[$this->_('Application baseuri')]     = $this->loader->getUtil()->getCurrentURI();
        $data[$this->_('Application directory')]   = APPLICATION_PATH;
        $data[$this->_('PHP version')]             = phpversion();
        $data[$this->_('Server Hostname')]         = php_uname('n');
        $data[$this->_('Server OS')]               = php_uname('s');
        $data[$this->_('Time on server')]          = date('r');

        $this->_showTable($this->_('Version information'), $data);
    }

    public function phpAction()
    {
        $this->html->h2($this->_('Server PHP Info'));

        $php = new MUtil_Config_Php();

        $this->view->headStyle($php->getStyle());
        $this->html->raw($php->getInfo());
    }

    public function privilegeAction()
    {
        $privileges = array();

        foreach ($this->acl->getPrivilegeRoles() as $privilege => $roles) {
            $privileges[$privilege][$this->_('Privilege')] = $privilege;
            $privileges[$privilege][$this->_('Allowed')]   = $roles[Zend_Acl::TYPE_ALLOW] ? implode(', ', $roles[Zend_Acl::TYPE_ALLOW]) : null;
            $privileges[$privilege][$this->_('Denied')]    = $roles[Zend_Acl::TYPE_DENY]  ? implode(', ', $roles[Zend_Acl::TYPE_DENY])  : null;
        }
        ksort($privileges);

        $this->html->h2($this->_('Project privileges'));
        $this->_showTable($this->_('Privileges'), $privileges, true);

        // $this->acl->echoRules();
    }

    public function projectAction()
    {
        $project = $this->project;
        unset($project['admin']);

        $this->html->h2($this->_('Project settings'));
        $this->_showTable(GEMS_PROJECT_NAME . 'Project.ini', $project);
    }


    public function roleAction()
    {
        $roles = array();

        foreach ($this->acl->getRolePrivileges() as $role => $privileges) {
            $roles[$role][$this->_('Role')]    = $role;
            $roles[$role][$this->_('Parents')] = $privileges[MUtil_Acl::PARENTS]   ? implode(', ', $privileges[MUtil_Acl::PARENTS])   : null;
            $roles[$role][$this->_('Allowed')] = $privileges[Zend_Acl::TYPE_ALLOW] ? implode(', ', $privileges[Zend_Acl::TYPE_ALLOW]) : null;
            $roles[$role][$this->_('Denied')]  = $privileges[Zend_Acl::TYPE_DENY]  ? implode(', ', $privileges[Zend_Acl::TYPE_DENY])  : null;
        }
        ksort($roles);

        $this->html->h2($this->_('Project roles'));
        $this->_showTable($this->_('Roles'), $roles, true);

        // $this->acl->echoRules();
    }

    public function sessionAction()
    {
        $this->html->h2($this->_('Session content'));
        $this->_showTable($this->_('Session'), $this->session);
    }
}