<?php

namespace Gems\AuthNew;

enum TfaAdapterType: string
{
    case Totp = TotpTfa::class;
}
