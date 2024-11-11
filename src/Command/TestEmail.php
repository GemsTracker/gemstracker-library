<?php

namespace Gems\Command;

use Gems\Helper\Env;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(name: 'email:test', description: 'Send a test email')]
class TestEmail extends Command
{
    public function __construct(
        protected readonly array $config,
        protected readonly MessageBusInterface $messageBus,
        protected readonly EventDispatcherInterface $eventDispatcher,
    )
    {
        parent::__construct(null);
    }

    protected function configure()
    {
        $this->addArgument('email', InputArgument::REQUIRED, sprintf('Target email address'));
        $this->addArgument('dsn', InputArgument::OPTIONAL, sprintf('Use DSN'));
        $this->addArgument('disable-message-bus', InputArgument::OPTIONAL, sprintf('Disable Message bus'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');
        $io = new SymfonyStyle($input, $output);

        if ($input->hasArgument('dsn')) {
            $dsn = $input->getArgument('dsn');
        } else {
            $dsn = $this->getDsn();
        }

        if (!$dsn) {
            $io->error('E-mail DSN not found');
            return static::FAILURE;
        }

        $transport = Transport::fromDsn($dsn, $this->eventDispatcher);


        $messageBus = $this->messageBus;
        if ($input->hasArgument('disable-message-bus') && $input->getArgument('disable-message-bus') == 1) {
            $messageBus = null;
        }

        $mailer = new Mailer($transport, $messageBus, $this->eventDispatcher);

        $now = new \DateTimeImmutable();
        $mail = new Email();
        $mail->from($this->getFrom($email));
        $mail->to($email);
        $mail->subject('Test email from gemstracker');
        $mail->text(sprintf(
            'This is a test e-mail sent at %s.',
            $now->format('Y-m-d H:i:s')
        ));

        try {
            $mailer->send($mail);
        } catch(\Exception $e) {

            $io->error(sprintf('Test mail failed. %s', $e->getMessage()));
            return static::FAILURE;
        }

        $io->success(sprintf('E-mail sent at %s', $now->format('Y-m-d H:i:s')));

        return static::SUCCESS;
    }

    protected function getDsn(): string
    {
        return Env::get('MAILER_DSN') ?? $this->config['email']['dsn'];
    }

    protected function getFrom(string $to): string
    {
        if (isset($this->config['email']['site'])) {
            return $this->config['email']['site'];
        }

        return $to;
    }
}