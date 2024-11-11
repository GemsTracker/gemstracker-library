<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Command
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Command;

use Gems\Console\ConsoleSettings;
use Gems\Exception\AuthenticationException;
use Gems\Repository\OrganizationRepository;
use Gems\User\UserLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @package    Gems
 * @subpackage Command
 * @since      Class available since version 1.0
 */
#[AsCommand(name: 'user:reset-password', description: 'Reset a password for a user')]
class ResetPassword extends Command
{
    public function __construct(
        protected readonly ConsoleSettings $consoleSettings,
        protected OrganizationRepository $organizationRepository,
        protected readonly UserLoader $userLoader,
        protected array $config,
    )
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('login', InputArgument::OPTIONAL, 'Username');
        $this->addArgument('organization', InputArgument::OPTIONAL, 'Organization ID the user should be created in');
        $this->addArgument('password', InputArgument::OPTIONAL, 'Password');
        $this->addArgument('password2', InputArgument::OPTIONAL, 'Repeat password');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!isset($this->config['console'], $this->config['console']['resetPassword'])
            || $this->config['console']['resetPassword'] !== true) {
            $io = new SymfonyStyle($input, $output);
            $io->error('Resetting passwords on the console has not been enabled');
            return static::FAILURE;
        }

        $this->consoleSettings->setConsoleUser();

        return $this->interactiveUserReset($input, $output);
    }

    protected function interactiveUserReset(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var QuestionHelper $helper
         */
        $helper = $this->getHelper('question');
        $io = new SymfonyStyle($input, $output);

        // User
        $login = $input->getArgument('login');
        if (! $login) {
            $question = new Question('User login name: ');
            $login = $helper->ask($input, $output, $question);
        }
        if ($login === null) {
            $io->error('Login name is required!');
            return static::FAILURE;
        }

        // Organization
        $organizationId = $input->getArgument('organization');
        if (! $organizationId) {
            $organizations = $this->organizationRepository->getOrganizations();
            $organizationsByName = array_flip($organizations);
            $question = new ChoiceQuestion('Organization: ', $organizations);
            $organization = $helper->ask($input, $output, $question);

            $organizationId = $organizationsByName[$organization];
        } else {
            $organizationId = intval($organizationId);
        }

        try {
            $user = $this->userLoader->getUser($login, $organizationId);
            // $io->text(get_class($user) . ' ' . $login . ' ' . $organizationId);
            if (! $user->isActive()) {
                 $user = false;
            }
        } catch (AuthenticationException $e) {
            $user = false;
        }
        if (! $user) {
            $io->error("User with login name '$login' for organization '$organizationId' is not active or does not exist!");
            return static::FAILURE;
        }

        // Password 1
        $password1 = $input->getArgument('password');
        if (! $password1) {
            $question = new Question('Enter password: ');
            $question->setHidden(true);
            $password1 = $helper->ask($input, $output, $question);
        }
        if ($password1 === null) {
            $io->error('Password is required!');
            return static::FAILURE;
        }

        // Password 2
        $password2 = $input->getArgument('password2');
        if (! $password2) {
            $question = new Question('Repeat password: ');
            $question->setHidden(true);
            $password2 = $helper->ask($input, $output, $question);
        }
        if ($password2 === null) {
            $io->error('Password repeat is required!');
            return static::FAILURE;
        }

        if ($password1 !== $password2) {
            $io->error('Password and password repeat are not the same!');
            return static::FAILURE;
        }
        $user->setPassword($password1);

        $io->success("Password reset for user $login was successful!");

        return static::SUCCESS;
    }

}