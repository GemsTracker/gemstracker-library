<?php
/**
 * Copyright (c) 2014, Erasmus MC
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
 * @package    MUtil
 * @subpackage Form_Decorator
 * @author     Jasper van Gestel <jappie@dse.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Table.php 1849 2014-03-21 16:12:53Z matijsdejong $
 */

/**
 * An extension to Zend Flashmessenger to allow for status updates in a flash message.
 * Each Message will be shown as a seperate message. You can group Messages in one status by passing it as an Array.
 *
 * @package    MUtil
 * @subpackage Form_Decorator
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

class MUtil_Controller_Action_Helper_FlashMessenger extends Zend_Controller_Action_Helper_FlashMessenger
{

	/**
	 * @var string The default status, if no status has been set.
	 */
	protected $_defaultStatus = 'warning';

	/**
	 * Add a message with a status
	 * @param string|array     $message   The message to add. You can group Messages in one status by passing them as an Array
	 * @param string $status    The status to add to the message
	 * @param string $namespace The messages namespace
	 */
	public function addMessage($message, $status = null, $namespace = null)
	{
		if (!$status) {
			$status = $this->_defaultStatus;
		}

        $message = array($message, $status);
		parent::addMessage($message, $namespace);
		return $this;
	}

    /**
     * Show Available messages in alerts. Bootstrap compatible
     * @return ErrorContainer Html nodes with the Errors.
     */
    public function showMessages()
    {
    	if ($this->hasMessages()) {
            $messages = $this->getMessages();
        } else {
            $messages = array();
        }

        if ($this->hasCurrentMessages()) {
            $messages = array_merge($messages, $this->getCurrentMessages());
        }

        $errorContainer = MUtil_Html::create()->div(array('class' => 'errors'));
        $errorClose = MUtil_Html::create()->button(array('type' => 'button','class' => 'close', 'data-dismiss' => 'alert'));
        $errorClose->raw('&times;');

        if ($messages) {
            foreach ($messages as &$message) {

                $status = 'warning';
                $multiMessage = false;
                if (is_array($message)) {
                 	if (is_string($message[1])) {
                    	$status = $message[1];
                    	$message = $message[0];
                    }
                    if (is_array($message)) {
                    	$message = MUtil_Html::create()->ul($message);
                    }
                }

                $error = MUtil_Html::create()->div(array('class' => 'alert alert-'.$status, 'role' => 'alert'));
                $error[] = $errorClose;
                $error[] = $message;
                $errorContainer[] = $error;
            }

            $this->clearCurrentMessages();

            return $errorContainer;
        }
    }
}
