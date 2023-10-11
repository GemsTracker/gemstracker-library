<?php


namespace Gems\Communication\Http;


interface SmsClientInterface
{
    public function sendMessage(string $number, string $body, string|null $originator = null): bool;
}
