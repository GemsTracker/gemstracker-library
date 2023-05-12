<?php

namespace Gems\Mail;

interface MailFieldsInterface
{
    /**
     * Return a list of filled in mail fields
     *
     * @return ?mixed[]
     */
    public function getMailFields(): array;
}