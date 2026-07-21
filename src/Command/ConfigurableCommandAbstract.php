<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Command
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Command;

use Gems\Encryption\ValueEncryptor;
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
abstract class ConfigurableCommandAbstract extends Command
{
    /**
     * @var array|array[]
     */
    protected array $arguments = [];

    public function __construct(
        protected ValueEncryptor $valueEncryptor,
    )
    {
        parent::__construct();
    }

    protected function configure()
    {
        foreach ($this->arguments as $name => $settings) {
            $this->addArgument($name, InputArgument::OPTIONAL, $settings['label']);
        }
    }

    protected function commandLineProjectInit(InputInterface $input, OutputInterface $output, array $defaults = []): int
    {
        $result = [];
        foreach ($this->arguments as $name => $settings) {
            $result[$name] = $input->getArgument($name);

            if ((null === $result[$name]) && ($settings['required'] ?? false)) {
                $io = new SymfonyStyle($input, $output);
                $io->error($settings['label'] . ' is required!');
                return static::FAILURE;
            }
        }

        return $this->saveSettings($result, $input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
        error_reporting(E_ALL);

        $defaults = $this->loadDefaults();
        if ($input->getArgument('database-user') === null) {
            return $this->interactiveProjectInit($input, $output, $defaults);
        }
        return $this->commandLineProjectInit($input, $output, $defaults);
    }

    protected function interactiveProjectInit(InputInterface $input, OutputInterface $output, array $defaults = []): int
    {
        /**
         * @var QuestionHelper $helper
         */
        $helper = $this->getHelper('question');

        $result = [];
        foreach ($this->arguments as $name => $settings) {
            $label = $settings['label'];
            if ($defaults[$name] ?? false) {
                if ($settings['encrypted'] ?? false) {
                    $defaults[$name] = $this->valueEncryptor->decrypt($defaults[$name]);
                }
                if (isset($settings['password'])) {
                    $label .= ' [********]';
                } else {
                    $label .= ' [' . $defaults[$name] . ']';
                }
            }
            if (isset($settings['choices'])) {
                $question = new ChoiceQuestion($label . ': ', $settings['choices'], $defaults[$name]);
            } else {
                $question = new Question($label . ': ');
                if (isset($settings['password'])) {
                    $question->setHidden(true);
                }
            }
            $result[$name] = $helper->ask($input, $output, $question);

            if (null === $result[$name]) {
                if ($defaults[$name] ?? false) {
                    $result[$name] = $defaults[$name];
                    continue;
                }

                if ($settings['required'] ?? false) {
                    $io = new SymfonyStyle($input, $output);
                    $io->error($settings['label'] . ' is required!');
                    return static::FAILURE;
                }
            }

            if (isset($settings['password'])) {
                $question = new Question($settings['label'] . ' repeat: ');
                $question->setHidden(true);

                $check = $helper->ask($input, $output, $question);

                if ($check !== $result[$name]) {
                    $io = new SymfonyStyle($input, $output);
                    $io->error($settings['label'] . ' and repeat are not the same!');
                    return static::FAILURE;
                }
            }

        }

        return $this->saveSettings($result, $input, $output);
    }

    public function loadDefaults(): array
    {
        return array_fill_keys(array_keys($this->arguments), '');
    }

    abstract protected function saveSettings(array $result, InputInterface $input, OutputInterface $output): int;
}