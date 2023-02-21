<?php

namespace Gems\Console;

use Gems\Legacy\CurrentUserRepository;
use Gems\User\UserLoader;

class ConsoleSettings
{
    public function __construct(
        protected UserLoader $userLoader,
        protected CurrentUserRepository $currentUserRepository,
    )
    {}

    public function setConsoleUser(): void
    {
        $consoleUser = $this->userLoader->getConsoleUser();
        $this->currentUserRepository->setCurrentUser($consoleUser);
        \Gems\Model::setCurrentUserId($consoleUser->getUserId());
    }
}