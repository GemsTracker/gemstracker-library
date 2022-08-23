<?php

namespace Gems\AuthNew;

enum AuthenticationAdapterType: string
{
    case GemsTracker = GemsTrackerAuthentication::class;
    case Epd = EpdAuthentication::class;
    case Ldap = LdapAuthentication::class;
    case Radius = RadiusAuthentication::class;
}
