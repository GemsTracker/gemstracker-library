<?php

/**
 * Copyright (c) 2015, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL MAGNAFACTA BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Snippets_Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: AppointmentCleanupSnippet.php $
 */

namespace Gems\Snippets\Agenda;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets_Agenda
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 13-mrt-2015 11:11:12
 */
class AppointmentCleanupSnippet extends \Gems_Snippets_ModelItemTableSnippetGeneric
{
    /**
     * The action to go to when the user clicks 'No'.
     *
     * If you want to change to another controller you'll have to code it.
     *
     * @var string
     */
    protected $abortAction = 'show';

    /**
     * @see Zend_Controller_Action_Helper_Redirector
     *
     * @var mixed Nothing or either an array or a string that is acceptable for Redector->gotoRoute()
     */
    protected $afterSaveRouteUrl;

    /**
     * One of the \MUtil_Model_Bridge_BridgeAbstract MODE constants
     *
     * @var int
     */
    protected $bridgeMode = \MUtil_Model_Bridge_BridgeAbstract::MODE_SINGLE_ROW;

    /**
     *
     * @var Zend_Cache_Core
     */
    protected $cache;

    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'displayer';

    /**
     * The action to go to when the user clicks 'Yes' and the data is deleted.
     *
     * If you want to change to another controller you'll have to code it.
     *
     * @var string
     */
    protected $cleanupAction = 'show';

    /**
     * The request parameter used to store the confirmation
     *
     * @var string Required
     */
    protected $confirmParameter = 'confirmed';

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var string|array the field or fields in appointments linking to this appointment
     */
    protected $filterOn;

    /**
     *
     * @var string The field in the model containing the yes/no filter
     */
    protected $filterWhen;

    /**
     * When hasHtmlOutput() is false a snippet user should check
     * for a redirectRoute.
     *
     * When hasHtmlOutput() is true this functions should not be called.
     *
     * @see Zend_Controller_Action_Helper_Redirector
     *
     * @return mixed Nothing or either an array or a string that is acceptable for Redector->gotoRoute()
     */
    public function getRedirectRoute()
    {
        return $this->afterSaveRouteUrl;
    }

    /**
     * Get the appointment where for this snippet
     *
     * @return string
     */
    protected function getWhere()
    {
        $id = intval($this->request->getParam(\MUtil_Model::REQUEST_ID));
        $add = " = " . $id;

        return implode($add . ' OR ', (array) $this->filterOn) . $add;
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        if ($this->request->getParam($this->confirmParameter)) {
            $this->performAction();

            $redirectRoute = $this->getRedirectRoute();
            return empty($redirectRoute);

        } else {
            return parent::hasHtmlOutput();
        }
    }

    /**
     * Overrule this function if you want to perform a different
     * action than deleting when the user choose 'yes'.
     */
    protected function performAction()
    {
        $count = $this->db->delete("gems__appointments", $this->getWhere());

        $this->addMessage(sprintf($this->plural(
                '%d appointment deleted.',
                '%d appointments deleted.',
                $count
                ), $count));


        $this->setAfterCleanupRoute();
    }

    /**
     * Set what to do when the form is 'finished'.
     */
    protected function setAfterCleanupRoute()
    {
        // Default is just go to the index
        if ($this->cleanupAction && ($this->request->getActionName() !== $this->cleanupAction)) {
            $this->afterSaveRouteUrl = array(
                $this->request->getControllerKey() => $this->request->getControllerName(),
                $this->request->getActionKey() => $this->cleanupAction,
                );
        }
    }

    /**
     * Set the footer of the browse table.
     *
     * Overrule this function to set the header differently, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function setShowTableFooter(\MUtil_Model_Bridge_VerticalTableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $fparams = array('class' => 'centerAlign');
        $row     = $bridge->getRow();

        if (isset($row[$this->filterWhen]) && $row[$this->filterWhen]) {
            $count = $this->db->fetchOne("SELECT COUNT(*) FROM gems__appointments WHERE " . $this->getWhere());

            if ($count) {
                $footer = $bridge->tfrow($fparams);

                $footer[] = sprintf($this->plural(
                        'This will delete %d appointment. Are you sure?',
                        'This will delete %d appointments. Are you sure?',
                        $count
                        ), $count);
                $footer[] = ' ';
                $footer->actionLink(
                        array($this->confirmParameter => 1),
                        $this->_('Yes')
                        );
                $footer[] = ' ';
                $footer->actionLink(
                        array($this->request->getActionKey() => $this->abortAction),
                        $this->_('No')
                        );

            } else {
                $this->addMessage($this->_('Clean up not needed!'));
                $bridge->tfrow($this->_('No clean up needed, no appointments exist.'), $fparams);
            }
        } else {
            $this->addMessage($this->_('Clean up filter disabled!'));
            $bridge->tfrow($this->_('No clean up possible.'), array('class' => 'centerAlign'));
        }

        if ($this->displayMenu) {
            if (! $this->menuList) {
                $this->menuList = $this->menu->getCurrentMenuList($this->request, $this->_('Cancel'));
                $this->menuList->addCurrentSiblings();
            }
            if ($this->menuList instanceof \Gems_Menu_MenuList) {
                $this->menuList->addParameterSources($bridge);
            }

            $bridge->tfrow($this->menuList, $fparams);
        }
    }
}
