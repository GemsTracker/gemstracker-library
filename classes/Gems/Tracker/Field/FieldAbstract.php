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
 * @subpackage Tracker_Field
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: FieldAbstract.php $
 */

namespace Gems\Tracker\Field;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker_Field
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 4-mrt-2015 11:40:28
 */
abstract class FieldAbstract extends \MUtil_Translate_TranslateableAbstract implements FieldInterface
{
    /**
     *
     * @var array  Field definition array
     */
    protected $_fieldDefinition;

    /**
     *
     * @var int gems__tracks id for this field
     */
    protected $_trackId;

    /**
     *
     * @param int $trackId gems__tracks id for this field
     * @param array $fieldDefinition Field definition array
     */
    public function __construct($trackId, array $fieldDefinition)
    {
        $this->_trackId         = $trackId;
        $this->_fieldDefinition = $fieldDefinition;
    }

    /**
     * Calculation the field info display for this type
     *
     * @param array $currentValue The current value
     * @param array $context The other values loaded so far
     * @return mixed the new value
     */
    public function calculateFieldInfo($currentValue, array $context)
    {
        return $currentValue;
    }

    /**
     *
     * @return The field code
     */
    public function getCode()
    {
        return $this->_fieldDefinition['gtf_field_code'];
    }

    /**
     *
     * @return The field label
     */
    public function getLabel()
    {
        return $this->_fieldDefinition['gtf_field_name'];
    }

    /**
     * Should this field be added to the track info
     *
     * @return boolean
     */
    public function toTrackInfo()
    {
        return $this->_fieldDefinition['gtf_to_track_info'];
    }
}
