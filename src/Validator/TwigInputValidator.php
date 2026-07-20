<?php

namespace Gems\Validator;

use Laminas\Validator\AbstractValidator;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Loader\ArrayLoader;
use Twig\Source;

class TwigInputValidator extends AbstractValidator
{
    public const INVALID      = 'twigInvalid';
    public const SYNTAX_ERROR = 'twigSyntaxError';

    protected array $messageTemplates = [
        self::INVALID      => 'Invalid type given; string expected',
        self::SYNTAX_ERROR => 'Twig syntax error: %error%',
    ];

    protected array $messageVariables = [
        'error' => 'errorMessage',
    ];

    protected ?string $errorMessage = null;

    public function __construct(
        $options = null,
    ) {
        parent::__construct($options);
    }

    public function isValid($value): bool
    {
        if (!is_string($value)) {
            $this->error(self::INVALID);
            return false;
        }

        $this->setValue($value);

        $normalizedValue = $this->normalize($value);

        $twigLoader = new ArrayLoader([
            'template' => $normalizedValue,
        ]);

        $twig = new Environment($twigLoader, [
            'autoescape' => false,
        ]);

        try {
            // tokenize + parse = lint (no compilation, no rendering)
            $twig->parse(
                $twig->tokenize(new Source($normalizedValue, 'twig_lint'))
            );
        } catch (SyntaxError $e) {
            $this->errorMessage = sprintf(
                '%s (line %d)',
                $e->getRawMessage(),
                $e->getTemplateLine()
            );
            $this->error(self::SYNTAX_ERROR);
            return false;
        }

        return true;
    }

    private function normalize(string $html): string
    {
        // One newline after each block close (and optional <br>).
        $pattern = '#(</(?:p|div|h[1-6]|li|ul|ol|blockquote|pre|figure|table|thead|tbody|tr|td|th)>|<br\s*/?>)#i';
        return preg_replace($pattern, "$1\n", $html);
    }
}