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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Tracker_Batch_SynchronizeSourcesBatch extends MUtil_Batch_BatchAbstract
{
    /**
     *
     * @var Gems_Tracker_Source_SourceInterface
     */
    private $_currentSource;

    /**
     *
     * @var Gems_Tracker
     */
    protected $tracker;

    /**
     *
     * @var Zend_Translate
     */
    protected $translate;

    /**
     *
     * @param mixed $sourceData Source Id or array containing source data
     * @param int $userId Gems user id
     */
    public function addSource($sourceData, $userId)
    {
        $this->_currentSource = $this->tracker->getSource($sourceData);
        $this->_currentSource->addSynchronizeSurveyCommands($this, $userId);
        $this->_currentSource = null;
        $this->addToSourceCounter();
    }

    /**
     *
     * @param mixed $sourceData Source Id or array containing source data
     */
    public function addSourceFunction($function, $param_args = null)
    {
        if (null === $this->_currentSource) {
            throw new Gems_Exception_Coding('Trying to add a Source Function without a current source.');
        }

        $params = array_slice(func_get_args(), 1);

        $this->addStep('sourceStep', $this->_currentSource->getId(), $function, $params);
    }

    /**
     * Add one to the number of sources checked
     *
     * @param int $add
     * @return int
     */
    public function addToSourceCounter($add = 1)
    {
        return $this->addToCounter('sources', $add);
    }

    /**
     * Add one to the number of surveys checked
     *
     * @param int $add
     * @return int
     */
    public function addToSurveyCounter($add = 1)
    {
        return $this->addToCounter('surveys', $add);
    }

    /**
     * String of messages from the batch
     *
     * Do not forget to reset() the batch if you're done with it after
     * displaying the report.
     *
     * @param boolean $reset When true the batch is reset afterwards
     * @return array
     */
    public function getMessages($reset = false)
    {

        $cSources = $this->getSourceCounter();
        $cSurveys = $this->getSurveyCounter();

        $messages = parent::getMessages($reset);

        if (! $messages) {
            $messages[] = $this->translate->_('No surveys were changed.');
        }
        array_unshift($messages, sprintf($this->translate->_('%d surveys checked.'), $cSurveys));
        if ($cSources > 1) {
            array_unshift($messages, sprintf($this->translate->_('%d sources checked.'), $cSources));
        }

        return $messages;
    }

    /**
     * Get the number of sources checked
     *
     * @return int
     */
    public function getSourceCounter()
    {
        return $this->getCounter('sources');
    }

    /**
     * Get the number of surveys checked
     *
     * @return int
     */
    public function getSurveyCounter()
    {
        return $this->getCounter('surveys');
    }

    /**
     * The basic steps
     *
     * @param int $sourceId
     * @param string $function
     * @param array $params
     */
    protected function sourceStep($sourceId, $function, array $params)
    {
        $source = $this->tracker->getSource($sourceId);

        $messages = call_user_func_array(array($source, $function), $params);

        if ($messages) {
            foreach ((array) $messages as $message) {
                $this->addMessage($message);
            }
        }
    }
}
