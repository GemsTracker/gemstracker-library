<?php

namespace Gems\AuthNew;

interface TfaAdapterInterface
{
    public function authenticate(): TfaResult;
}
