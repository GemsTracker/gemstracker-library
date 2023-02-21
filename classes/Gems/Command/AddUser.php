<?php

namespace Gems\Command;

use Exception;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Gems\Console\ConsoleSettings;
use Gems\Model\StaffModel;
use Gems\Repository\AccessRepository;
use Gems\Repository\OrganizationRepository;
use Gems\Repository\StaffRepository;
use Gems\Util\Translated;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'user:add-user', description: 'Add a new user')]
class AddUser extends Command
{
    public function __construct(
        protected StaffRepository $staffRepository,
        protected AccessRepository $accessRepository,
        protected OrganizationRepository $organizationRepository,
        protected Translated $translatedUtil,
        protected ConsoleSettings $consoleSettings,
        protected array $config,
    )
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('name', InputArgument::OPTIONAL, 'Username');
        $this->addArgument('organization', InputArgument::OPTIONAL, 'Organization ID the user should be created in');
        $this->addArgument('group', InputArgument::OPTIONAL, 'User group');

        $this->addArgument('lastname', InputArgument::OPTIONAL, 'Last name');
        $this->addArgument('email', InputArgument::OPTIONAL, 'E-mail');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!isset($this->config['console'], $this->config['console']['addUsers'])
            || $this->config['console']['addUsers'] !== true) {
            $io = new SymfonyStyle($input, $output);
            $io->error('Adding users on the console has not been enabled');
            return static::FAILURE;
        }

        $this->consoleSettings->setConsoleUser();

        if ($input->getArgument('name') === null) {
            return $this->interactiveUserAdd($input, $output);
        }
        return $this->manualUserAdd($input, $output);
    }

    protected function getGroupId(string|int $group): int|null
    {
        if (is_int($group)) {
            return $group;
        }

        $groups = array_flip($this->accessRepository->getGroups());
        if (isset($groups[$group])) {
            return $groups[$group];
        }
        return null;
    }

    protected function interactiveUserAdd(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var $helper QuestionHelper
         */
        $helper = $this->getHelper('question');
        $io = new SymfonyStyle($input, $output);

        $question = new Question('User name: ');
        $username = $helper->ask($input, $output, $question);

        if ($username === null) {
            $io->error('Name is required!');
            return static::FAILURE;
        }

        // Organization
        $organizations = $this->organizationRepository->getOrganizations();
        $organizationsByName = array_flip($organizations);

        $question = new ChoiceQuestion('Organization: ', $organizations);
        $organization = $helper->ask($input, $output, $question);

        $organizationId = $organizationsByName[$organization];

        // Group
        $groups = $this->accessRepository->getGroups();
        if (isset($groups[''])) {
            unset($groups['']);
        }
        $groupsByName = array_flip($groups);

        $question = new ChoiceQuestion('Group: ', $groups);
        $group = $helper->ask($input, $output, $question);

        $groupId = $groupsByName[$group];

        $question = new Question('<optional>First name: ');
        $firstName = $helper->ask($input, $output, $question);
        $question = new Question('<optional>surname prefix: ');
        $surnamePrefix = $helper->ask($input, $output, $question);

        $question = new Question('Last name: ');
        $lastName = $helper->ask($input, $output, $question);

        if ($lastName === null) {
            $io->error('Last name is required!');
            return static::FAILURE;
        }

        $question = new ConfirmationQuestion('Would you like to set additional user values?', 'no');
        $continue = $helper->ask($input, $output, $question);

        $email = null;
        $phoneNumber = null;
        $jobTitle = null;
        $gender = null;


        if ($continue) {
            $question = new Question('<optional>E-mail: ');
            $email = $helper->ask($input, $output, $question);

            $question = new Question('<optional>Phone number: ');
            $phoneNumber = $helper->ask($input, $output, $question);

            $question = new Question('<optional>Job title: ');
            $jobTitle = $helper->ask($input, $output, $question);

            $genders = $this->translatedUtil->getGenders();
            $gendersByName = array_flip($genders);
            $question = new ChoiceQuestion('<optional>Gender: ', $genders, 'Unknown');
            $rawGender = $helper->ask($input, $output, $question);
            if (isset($genders[$rawGender])) {
                $gender = $genders[$rawGender];
            } elseif(isset($gendersByName[$rawGender])) {
                $gender = $gendersByName[$rawGender];
            }
        }



        $io->writeln('-----------------');
        $io->writeln(sprintf('Name: %s', $username));
        $io->writeln(sprintf('Organization: %s', $organization));
        $io->writeln(sprintf('Group: %s', $group));
        if ($firstName !== null) {
            $io->writeln(sprintf('First name: %s', $firstName));
        }
        if ($surnamePrefix !== null) {
            $io->writeln(sprintf('Surname prefix: %s', $surnamePrefix));
        }
        $io->writeln(sprintf('Last name: %s', $lastName));
        if ($email !== null) {
            $io->writeln(sprintf('Email: %s', $email));
        }
        if ($jobTitle !== null) {
            $io->writeln(sprintf('Job title: %s', $jobTitle));
        }
        if ($gender !== null) {
            $io->writeln(sprintf('Gender: %s', $gender));
        }
        $io->writeln('-----------------');

        $question = new ConfirmationQuestion('Is the above information correct?');
        $correct = $helper->ask($input, $output, $question);

        if (!$correct) {
            $io->info('User aborted');
            return static::FAILURE;
        }

        try {
            $result = $this->staffRepository->createStaff(
                username: $username,
                organizationId: $organizationId,
                groupId: $groupId,
                lastName: $lastName,
                firstName: $firstName,
                surnamePrefix: $surnamePrefix,
                email: $email,
                phoneNumber: $phoneNumber,
                jobTitle: $jobTitle,
                gender: $gender,
            );

        } catch(Exception $e) {
            $io->error('Creating user failed:');
            $io->error($e->getMessage());
            return static::FAILURE;
        }

        return static::SUCCESS;
    }

    protected function manualUserAdd(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('name');

        $organizationId = $input->getArgument('organizationId');
        if ($organizationId === null) {
            $io->error('organizationId parameter missing in manual input');
            return static::FAILURE;
        }
        if (!is_numeric($organizationId)) {
            $io->error('organization can only be an ID');
            return static::FAILURE;
        }
        $organizations = $this->organizationRepository->getOrganizations();
        if (!isset($organizations[$organizationId])) {
            $io->error(sprintf('Organization ID %d not found', (int)$organizationId));
            return static::FAILURE;
        }

        $group = $input->getArgument('group');
        if ($group === null) {
            $io->error('Group parameter missing in manual input');
            return static::FAILURE;
        }
        $groupId = $this->getGroupId($group);
        if ($groupId === null) {
            $io->error(sprintf('Group %s not found', $group));
            return static::FAILURE;
        }

        $lastName = $input->getArgument('lastname');
        if ($lastName === null) {
            $io->error('lastname parameter missing in manual input');
            return static::FAILURE;
        }

        $email = $input->getArgument('email');
        if ($email !== null) {
            $validator = new EmailValidator();
            if (!$validator->isValid($email, new RFCValidation())) {
                $io->error(sprintf('email %s is not a valid email', $email));
                return static::FAILURE;
            }
            $values['gsf_email'] = $email;
        }

        try {
            $result = $this->staffRepository->createStaff(
                username: $username,
                organizationId: $organizationId,
                groupId: $groupId,
                lastName: $lastName,
                email: $email,
            );

        } catch(Exception $e) {
            $io->error('Creating user failed:');
            $io->error($e->getMessage());
            return static::FAILURE;
        }

        return static::SUCCESS;
    }
}