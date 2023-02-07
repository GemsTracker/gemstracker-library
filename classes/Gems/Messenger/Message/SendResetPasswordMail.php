<?php

class SendResetPasswordMail
{
    public function __construct(private string $loginName, private int $organizationId)
    {}

    /**
     * @return string
     */
    public function getLoginName(): string
    {
        return $this->loginName;
    }

    /**
     * @return int
     */
    public function getOrganizationId(): int
    {
        return $this->organizationId;
    }

}