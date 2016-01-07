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
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Round.php 2493 2015-04-15 16:29:48Z matijsdejong $
 */

namespace Gems\Tracker;

use MUtil\Ra\RaObject;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 7, 2016 11:26:57 AM
 */
class Round extends RaObject
{
    /**
     *
     * @var \Gems_Tracker_Survey
     */
    protected $_survey = false;

    /**
     *
     * @var \Gems_Tracker
     */
    protected $tracker;

    /**
     * Get the survey id for this round
     *
     * @return int
     */
    public function getSurvey()
    {
        if (false !== $this->_survey) {
            return $this->_survey;
        }
        $surveyId = $this->getSurveyId();

        if ($surveyId) {
            $this->_survey = $this->tracker->getSurvey($surveyId);
        } else {
            $this->_survey = null;
        }

        return $this->_survey;
    }

    /**
     * Get the survey id for this round
     *
     * @return int
     */
    public function getSurveyId()
    {
        return $this->offsetDefault('gro_id_survey');
    }

    /**
     * Is this an active round
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->offsetDefault('gro_active', false);
    }

}
