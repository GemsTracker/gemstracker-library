<?php

namespace Gems\Handlers;

use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

class SnippetLegacyHandlerAbstract extends \MUtil\Handler\SnippetLegacyHandlerAbstract
{
    protected array $_defaultParameters = [];
    protected array $defaultParameters = [];

    public function __construct(SnippetResponderInterface $responder, TranslatorInterface $translate)
    {
        parent::__construct($responder, $translate);
        if (! $this->html) {
            \Gems\Html::init();
        }
    }

    /**
     *
     * @param array $input
     * @return array
     */
    protected function _processParameters(array $input): array
    {
        $output = [];

        foreach ($input + $this->defaultParameters + $this->_defaultParameters as $key => $value) {
            if (is_string($value) && method_exists($this, $value)) {
                $value = $this->$value();

                if (is_integer($key) || ($value === null)) {
                    continue;
                }
            }
            $output[$key] = $value;
        }

        return $output;
    }
}