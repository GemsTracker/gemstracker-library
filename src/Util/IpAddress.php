<?php

namespace Gems\Util;

use IPLib\Address\AddressInterface;
use IPLib\Factory as IpFactory;
use IPLib\Range\RangeInterface;

class IpAddress
{
    /**
     * Checks if a given IP is allowed according to a set
     * of IP addresses / ranges.
     *
     * Multiple addresses/ranges are separated by a colon,
     * an individual range takes the form of
     * Separate with | examples: 10.0.0.0-10.0.0.255, 10.10.*.*, 10.10.151.1 or 10.10.151.1/25
     *
     * @param  string $ip
     * @param  string $ipRanges
     * @return bool
     */
    public static function isAllowed(string $ip, string $ipRanges = ""): bool
    {
        $address = IpFactory::parseAddressString($ip);
        if (! (($address instanceof AddressInterface) && strlen($ipRanges))) {
            return true;
        }
        $aType = $address->getAddressType();

        $ranges = explode('|', $ipRanges);
        foreach ($ranges as $range) {
            if (($sep = strpos($range, '-')) !== false) {
                $rangeIF = IpFactory::getRangeFromBoundaries(substr($range, 0, $sep), substr($range, $sep + 1));

            } else {
                $rangeIF = IpFactory::parseRangeString($range);
            }

            if (($rangeIF instanceof RangeInterface) &&
                $rangeIF->getAddressType() == $aType &&
                $rangeIF->contains($address)) {
                return true;
            }
        }
        return false;
    }
}