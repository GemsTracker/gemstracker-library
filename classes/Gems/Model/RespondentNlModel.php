<?php

/**
 *
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
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
class Gems_Model_RespondentNlModel extends \Gems_Model_RespondentModel
{
    /**
     * Set those settings needed for the detailed display
     *
     * @return \Gems_Model_RespondentNlModel
     */
    public function applyDetailSettings()
    {
        parent::applyDetailSettings();

        $this->setIfExists('grs_surname_prefix', 'description', $this->_('de, van der, \'t, etc...'));
        $this->setIfExists('grs_partner_surname_prefix', 'description', $this->_('de, van der, \'t, etc...'));

        return $this;
    }

    /**
     * Set those values needed for editing
     *
     * @param boolean $create True when creating
     * @return \Gems_Model_RespondentModel
     */
    public function applyEditSettings($create = false)
    {
        parent::applyEditSettings($create);

        $translator = $this->getTranslateAdapter();

        if ($this->hashSsn !== parent::SSN_HIDE) {
            self::setDutchSsn($this, $translator);
        }

        $this->setIfExists('grs_iso_lang', 'default', 'nl');
        $this->setIfExists('gr2o_treatment', 'description', $this->_('DBC\'s, etc...'));

        self::setDutchZipcode($this, $translator);

        return $this;
    }

    /**
     * Set the field values for a dutch social security number
     *
     * @param \MUtil_Model_ModelAbstract $model
     * @param \Zend_Translate_Adapter $translator
     * @param string $fieldName
     */
    public static function setDutchSsn(\MUtil_Model_ModelAbstract $model, \Zend_Translate_Adapter $translator, $fieldName = 'grs_ssn')
    {
        $bsn = new \MUtil_Validate_Dutch_Burgerservicenummer();

        $model->set($fieldName,
                'size', 10,
                'maxlength', 12,
                'filter', new \MUtil_Filter_Dutch_Burgerservicenummer(),
                'validators[bsn]', $bsn);

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
     * @param \MUtil_Model_ModelAbstract $model
     * @param \Zend_Translate_Adapter $translator
     * @param string $fieldName
     */
    public static function setDutchZipcode(\MUtil_Model_ModelAbstract $model, \Zend_Translate_Adapter $translator, $fieldName = 'grs_zipcode')
    {
        $model->set($fieldName,
                'size', 7,
                'description', $translator->_('E.g.: 0000 AA'),
                'filter', new \Gems_Filter_DutchZipcode()
                );
    }
}
