<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage AuthNew
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\AuthNew;

use Psr\Http\Message\ServerRequestInterface;

/**
 * @package    Gems
 * @subpackage AuthNew
 * @since      Class available since version 1.0
 */
class IpFinder
{
    public static function getClientIp(ServerRequestInterface $request): ?string
    {
        $params = $request->getServerParams();
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $field) {
            if (isset($params[$field])) {
                return $params[$field];
            }
        }
        return null;
    }
}