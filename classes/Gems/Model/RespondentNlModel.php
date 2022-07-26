<?php

/**
 *
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Model;

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
class RespondentNlModel extends \Gems\Model\RespondentModel
{
    /**
     * Set those settings needed for the detailed display
     *
     * @return \Gems\Model\RespondentNlModel
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
     * @return \Gems\Model\RespondentModel
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
     * Return a hashed version of the input value.
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return string The output to display
     */
    public function hideSSN($value, $isNew = false, $name = null, array $context = array(), $isPost = false)
    {
        $value = parent::hideSSN($value, $isNew, $name, $context, $isPost);
        if ($value) {
            $this->set('grs_ssn', 'description', $this->_('Empty this field to remove the BSN'));
        }
        return $value;
    }


    /**
     * Set the field values for a dutch social security number
     *
     * @param \MUtil\Model\ModelAbstract $model
     * @param \Zend_Translate_Adapter $translator
     * @param string $fieldName
     */
    public static function setDutchSsn(\MUtil\Model\ModelAbstract $model, \Zend_Translate_Adapter $translator, $fieldName = 'grs_ssn')
    {
        $bsn = new \MUtil_Validate_Dutch_Burgerservicenummer();

        $model->set($fieldName,
                'size', 10,
                'maxlength', 12,
                'filter', new \MUtil\Filter\Dutch\Burgerservicenummer(),
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
     * @param \MUtil\Model\ModelAbstract $model
     * @param \Zend_Translate_Adapter $translator
     * @param string $fieldName
     */
    public static function setDutchZipcode(\MUtil\Model\ModelAbstract $model, \Zend_Translate_Adapter $translator, $fieldName = 'grs_zipcode')
    {
        $model->set($fieldName,
                'size', 7,
                'description', $translator->_('E.g.: 0000 AA'),
                'filter', new \Gems\Filter\DutchZipcode()
                );
    }
}
