<?php

namespace Gems\AuthTfa\Method;

use Gems\AuthTfa\Adapter\OtpAdapterInterface;

interface OtpMethodInterface extends OtpAdapterInterface
{
    public function getCodeInputDescription(): string;

    public function addSetupFormElements(\Zend_Form $form, array $formData);
}
