<?php


namespace Gems\Communication\Http;


interface SmsClientInterface
{
    public function sendMessage($number, $body, $originator=null);
}
