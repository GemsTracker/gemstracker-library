<?php

namespace Gems\AuthNew\Adapter;

enum AuthenticationIdentityType: string
{
    case GemsTracker = GemsTrackerIdentity::class;
    case Embed = EmbedIdentity::class;
}
