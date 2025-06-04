<?php

/*

/**
 * Allow encryption of some database fields like passwords
 *
 * Only use for passwords the application needs to use like database passwords
 * etc. The user passwords are stored using a one-way encryption.
 *
 * @package    Gems
 * @subpackage Model\Type
 * @author     175780
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Model\Type;

use Gems\Encryption\ValueEncryptor;
use MUtil\Model\DatabaseModelAbstract;
use Zalt\Model\MetaModelInterface;

/**
 *
 * @package    Gems
 * @subpackage Model\Type
 * @copyright  Copyright (c) 2017, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.7
 */
class EncryptedField
{
    /**
     * @var string
     */
    protected string $maskedValue = '********';

    /**
     * Should the value be masked?
     *
     * @var bool
     */
    protected bool $valueMask;

    protected  ValueEncryptor $valueEncryptor;

    public function __construct(ValueEncryptor $valueEncryptor, bool $valueMask = true)
    {
        $this->valueMask = $valueMask;
        $this->valueEncryptor = $valueEncryptor;
    }

    /**
     * Use this function for a default application of this type to the model
     *
     * @param string $valueField The field containing the value to be encrypted
     * @return EncryptedField (continuation pattern)
     */
    public function apply(MetaModelInterface $model, string $valueField): self
    {
        $model->setSaveWhen($valueField, array($this, 'saveWhen'));
        $model->setOnLoad($valueField, array($this, 'loadValue'));
        $model->setOnSave($valueField, array($this, 'saveValue'));

        if ($model instanceof DatabaseModelAbstract) {
            $model->setOnTextFilter($valueField, false);
        }
        if ($model->get($valueField, 'repeatLabel')) {
            $repeatField = $valueField . '__repeat';
            $model->set($repeatField, 'elementClass', 'None');
            $model->setSaveWhen($repeatField, false);
            $model->setOnLoad($repeatField, array($this, 'loadValue'));
        }

        return $this;
    }

    /**
     * A ModelAbstract->setOnLoad() function that takes care of transforming a value
     *
     * If empty or \Zend_Db_Expression (after save) it will return just the value
     * currently there are no checks for a valid date format.
     *
     * @param mixed $value The value being saved
     * @param bool $isNew True when a new item is being saved
     * @param string|null $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param bool $isPost True when passing on post data
     * @return mixed
     */
    public function loadValue(mixed $value, bool $isNew = false, string|null $name = null, array $context = [], bool $isPost = false): mixed
    {
        if (str_ends_with($name, '__repeat')) {
            // Fill value for repeat element
            $origName = substr($name, 0, - 8);
            if (isset($context[$origName])) {
                $value = $context[$origName];
            }
        }
        if ($value && (! $isPost)) {
            if ($this->valueMask) {
                return $this->maskedValue;
            } else {
                return $this->valueEncryptor->decrypt($value);
            }
        }

        return $value;
    }

    /**
     * A ModelAbstract->setOnSave() function that returns a value
     *
     * @param mixed $value The value being saved
     * @param bool $isNew True when a new item is being saved
     * @param string|null $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return string|null
     */
    public function saveValue(mixed $value, bool $isNew = false, string|null $name = null, array $context = []): string|null
    {
        if ($value) {
            return $this->valueEncryptor->encrypt($value);
        }
        return null;
    }

    /**
     * @param mixed $value The value being saved
     * @param bool $isNew True when a new item is being saved
     * @param string|null $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return bool
     */
    public function saveWhen(mixed $value, bool $isNew = false, string|null $name = null, array $context = []): bool
    {
        return $value && $this->maskedValue != $value;
    }
}
