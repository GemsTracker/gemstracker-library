<?php

declare(strict_types=1);

namespace Gems\Communication\Handler;

use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class TestCommunicationEmailRequest
{
    public function __construct(
        #[NotBlank]
        public readonly string $type,
        #[NotBlank]
        #[Email]
        public readonly string $to,
        #[NotBlank]
        public readonly string $subject,
        #[NotBlank]
        public readonly string $body,
        public readonly string|int|null $context,
        public readonly int|null $organizationId = null,
    )
    {}
}