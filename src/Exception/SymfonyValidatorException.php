<?php

declare(strict_types=1);

namespace Gems\Exception;

use RuntimeException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Throwable;

class SymfonyValidatorException extends RuntimeException
{
    private ConstraintViolationListInterface $violations;

    public function __construct(
        ConstraintViolationListInterface $violations,
        string $message = 'Validation failed',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->violations = $violations;
    }

    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
    }

    public function getFormattedViolations(): array
    {
        $errors = [];
        foreach ($this->violations as $violation) {
            // Use propertyPath to group errors by field
            $propertyPath = $violation->getPropertyPath();
            // Remove brackets [] for array access representation if needed
            $propertyPath = str_replace(['[', ']'], '', $propertyPath);
            if (!isset($errors[$propertyPath])) {
                $errors[$propertyPath] = [];
            }
            $errors[$propertyPath][] = $violation->getMessage();
        }
        // If you prefer a flat list or just the first error per field:
        // foreach ($this->violations as $violation) {
        //     $errors[$violation->getPropertyPath()] = $violation->getMessage();
        // }
        return $errors;
    }
}