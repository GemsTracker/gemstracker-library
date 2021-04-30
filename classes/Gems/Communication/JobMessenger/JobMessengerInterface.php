<?php


namespace Gems\Communication\JobMessenger;


interface JobMessengerInterface
{
    public function sendCommunication(array $job, array $tokenData, $preview);
}
