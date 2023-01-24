<?php


namespace Gems\Communication\JobMessenger;


use Gems\Tracker\Token;

interface JobMessengerInterface
{
    public function sendCommunication(array $job, Token $token, bool $preview): ?bool;
}
