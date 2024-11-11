<?php

namespace Gems\AuthNew\Adapter;

interface HasSessionKeyInterface
{
    public function getSessionKey(?string $default = null);
}