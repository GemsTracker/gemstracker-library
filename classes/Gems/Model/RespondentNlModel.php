<?php

/**
 * Copyright (c) 2012, Erasmus MC
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
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $id: RespondentNlModel.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 * Class containing the Netherlands specific model extensions.
 *
 * Extend your project specific RespondentModel from this model to make it go Dutch.
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6
 */
class Gems_Model_RespondentNlModel extends Gems_Model_RespondentModel
{
    /**
     * Set those settings needed for the detailed display
     *
     * @param mixed $locale The locale for the settings
     * @return \Gems_Model_RespondentModel
     */
    public function applyDetailSettings($locale = null)
    {
        parent::applyDetailSettings($locale);

        $translator = $this->getTranslateAdapter();

        $this->setIfExists('grs_surname_prefix', 'description', $translator->_('de, van der, \'t, etc...'));
        $this->setIfExists('grs_partner_surname_prefix', 'description', $translator->_('de, van der, \'t, etc...'));

        return $this;
    }

    /**
     * Set those values needed for editing
     *
     * @param mixed $locale The locale for the settings
     * @return \Gems_Model_RespondentModel
     */
    public function applyEditSettings($locale = null)
    {
        parent::applyEditSettings($locale);
        $translator = $this->getTranslateAdapter();

        if ($this->hashSsn !== Gems_Model_RespondentModel::SSN_HIDE) {
            self::setDutchSsn($this, $translator);
        }

        $this->setIfExists('grs_iso_lang', 'default', 'nl');
        $this->setIfExists('gr2o_treatment', 'description', $translator->_('DBC\'s, etc...'));

        self::setDutchZipcode($this, $translator);

        return $this;
    }

    /**
     * Set the field values for a dutch social security number
     *
     * @param MUtil_Model_ModelAbstract $model
     * @param Zend_Translate_Adapter $translator
     * @param string $fieldName
     */
    public static function setDutchSsn(MUtil_Model_ModelAbstract $model, Zend_Translate_Adapter $translator, $fieldName = 'grs_ssn')
    {
        $bsn = new MUtil_Validate_Dutch_Burgerservicenummer();

        $match = '/[^0-9\*]/';
        /*
        $m = preg_quote($model->hideSSN(true));
        $match = '/(?>(?(?=' . $m . ')(?!' . $m . ').|[^0-9]))/';
        MUtil_Echo::track($match);
        // */
        $model->set($fieldName,
                'size', 10,
                'maxlength', 12,
                'filter', new Zend_Filter_PregReplace(array('match' => $match)),
                'validator[]', $bsn);

        if (APPLICATION_ENV !== 'production') {
            $num = mt_rand(100000000, 999999999);

            while (! $bsn->isValid($num)) {
                $num++;
            }

            $model->set($fieldName, 'description', sprintf($translator->_('Random Example BSN: %s'), $num));
        } else {
            $model->set($fieldName, 'description', $translator->_('Enter a 9-digit SSN number.'));
        }
    }

    /**
     * Set the field values for a dutch zipcode
     *
     * @param MUtil_Model_ModelAbstract $model
     * @param Zend_Translate_Adapter $translator
     * @param string $fieldName
     */
    public static function setDutchZipcode(MUtil_Model_ModelAbstract $model, Zend_Translate_Adapter $translator, $fieldName = 'grs_zipcode')
    {
        $model->set($fieldName,
                'size', 7,
                'description', $translator->_('E.g.: 0000 AA'),
                'filter', new Gems_Filter_DutchZipcode()
                );
    }
}
