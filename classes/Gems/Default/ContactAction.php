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
class Gems_Default_ContactAction  extends Gems_Controller_Action
{
    public function aboutAction()
    { 
        $this->_forward('support');
    }

    public function bugsAction()
    { }

    private function getOrganizationsList()
    {
        $html = Gems_Html::init();
        $sql  = '
            SELECT *
            FROM gems__organizations
            WHERE gor_active=1 AND gor_url IS NOT NULL AND gor_task IS NOT NULL
            ORDER BY gor_name';

        $organizations = $this->db->fetchAll($sql);
        $orgCount      = count($organizations);


        switch ($orgCount) {
            case 0:
                return $html->pInfo(sprintf($this->_('%s is a web application.'), $this->project->name));

            case 1:
                $organization = reset($organizations);

                $p = $html->pInfo(sprintf($this->_('The %s project is run by: '), $this->project->name));
                $p->a($organization['gor_url'], $organization['gor_name']);

                return $p;

            default:
                $p = $html->pInfo(sprintf($this->_('%s is a collaboration of these organizations:'), $this->project->name));

                $data = MUtil_Lazy::repeat($organizations);
                $ul = $p->ul($data, array('class' => 'indent'));
                $li = $ul->li();
                $li->a($data->gor_url->call($this, '_'), $data->gor_name, array('rel' => 'external'));
                $li->append(' (');
                $li->append($data->gor_task->call(array($this, '_')));
                $li->append(')');

                return $p;
        }
    }

    public function indexAction()
    {
        $this->initHtml();
        Gems_Html::init();

        $this->html->h3($this->_('Contact'));

        $this->html->h4(sprintf($this->_('The %s project'), $this->project->name));
        $this->html->append($this->getOrganizationsList());

        $this->html->h4($this->_('Information on this application'));
        $this->html->pInfo($this->_('Links concerning this web application:'));

        $menuItem = $this->menu->getCurrent();
        $ul = $menuItem->toUl($this->getRequest());
        $ul->class = 'indent';
        $this->html[] = $ul;
    }

    /* 
    public function requestAccountAction()
    {
        $this->initHtml();

        $this->html->h3($this->_('Request account'));


    } */

    public function supportAction()
    {
        $this->view->organizationsList = $this->getOrganizationsList();
    }
}
