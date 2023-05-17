<?php

namespace Gems\Communication\Http;

use Gems\Communication\CommunicationRepository;
use Symfony\Component\Mime\Address;

class DevMailSmsClient implements SmsClientInterface
{
    public function __construct(
        private readonly array $config,
        private readonly CommunicationRepository $communicationRepository
    )
    {
    }

    public function sendMessage($number, $body, $originator=null)
    {
        $reference = sprintf('%s: %s', $this->config['app']['name'], $this->config['app']['env']);

        $message = [
            'encoding' => 'auto',
            'body' => $body,
            'originator' => $originator,
            'route' => 'business',
            'reference' => $reference,
            'recipients' => [
                $number,
            ]
        ];

        if (isset($this->config, $this->config['route'])) {
            $message['route'] = $this->config['route'];
        }
        if (isset($this->config, $this->config['reference'])) {
            $message['reference'] = $this->config['reference'];
        }

        $options = [
            'json' => $message,
        ];


        $email = $this->communicationRepository->getNewEmail();
        $email->addTo(new Address('mailhog@example.com', 'Local'));
        $email->addFrom(new Address('dev@example.com'));

        $mailer = $this->communicationRepository->getMailer();

        $email->subject('Redirected SMS');
        $email->text(var_export($options, true));

        $mailer->send($email);

        return true;
    }
}
