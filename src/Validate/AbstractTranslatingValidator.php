<?php

namespace Gems\Validate;

use Laminas\Validator\AbstractValidator;

abstract class AbstractTranslatingValidator extends AbstractValidator
{
    public function _(string $id): string
    {
        return $this->getTranslator()->translate($id);
    }
}
