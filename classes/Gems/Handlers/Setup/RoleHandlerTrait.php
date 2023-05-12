<?php

namespace Gems\Handlers\Setup;

use Gems\Menu\Menu;
use Gems\Middleware\MenuMiddleware;

trait RoleHandlerTrait
{
    /**
     *
     * @var array
     */
    protected $usedPrivileges;

    /**
     * Get the privileges a role can have.
     *
     * @return array
     */
    protected function getUsedPrivileges()
    {
        if (! $this->usedPrivileges) {
            /** @var Menu $menu */
            $menu = $this->request->getAttribute(MenuMiddleware::MENU_ATTRIBUTE);
            $routeLabelsByPrivilege = $menu->getRouteLabelsByPrivilege();
            $privileges = $this->routeHelper->getAllRoutePrivileges();
            $supplementaryPrivileges = $this->aclRepository->getSupplementaryPrivileges();

            $privilegeNames = [];
            foreach ($routeLabelsByPrivilege as $privilege => $labels) {
                $privilegeNames[$privilege] = implode("<br/>&nbsp; + ", $labels);
            }

            asort($privilegeNames);
            asort($privileges);

            foreach ($privileges as $privilege => $label) {
                if (!isset($privilegeNames[$privilege])) {
                    $privilegeNames[$privilege] = $label;
                }
            }

            asort($supplementaryPrivileges);
            foreach ($supplementaryPrivileges as $privilege => $label) {
                if (!isset($privilegeNames[$privilege])) {
                    $privilegeNames[$privilege] = $label->trans($this->translate);
                }
            }

            //don't allow to edit the pr.nologin and pr.islogin privilege
            unset($privilegeNames['pr.nologin']);
            unset($privilegeNames['pr.islogin']);

            $this->usedPrivileges = $privilegeNames;
        }

        return $this->usedPrivileges;
    }
}
