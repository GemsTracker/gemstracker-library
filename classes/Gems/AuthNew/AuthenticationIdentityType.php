<?php

namespace Gems\AuthNew;

enum AuthenticationIdentityType: string
{
    case GemsTracker = GemsTrackerIdentity::class;
    case Embed = EmbedIdentity::class;
}
