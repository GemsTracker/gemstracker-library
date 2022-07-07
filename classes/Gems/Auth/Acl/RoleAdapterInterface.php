<?php

namespace Gems\Auth\Acl;

interface RoleAdapterInterface
{
    public function getRoles(): array;
}
