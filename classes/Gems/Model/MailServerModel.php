<?php

namespace Gems\Model;

use Gems\Encryption\ValueEncryptor;
use Gems\Mail;
use Gems\Model;
use Gems\Model\Type\EncryptedField;

class MailServerModel extends JoinModel
{
    /**
     * @var ValueEncryptor
     */
    protected $valueEncryptor;

    public function __construct()
    {
        parent::__construct('mailServers', 'gems__mail_servers', 'gms', true);
    }

    public function afterRegistry()
    {
        // Key can be changed by users
        $this->copyKeys();

        $this->set(
            'gms_from',
            'label',
            $this->_('From address [part]'),
            'size',
            30,
            'description',
            $this->_("E.g.: '%', '%.org' or '%@gemstracker.org' or 'root@gemstracker.org'.")
        );
        $this->set('gms_server', 'label', $this->_('Server'), 'size', 30);
        $this->set(
            'gms_ssl',
            'label',
            $this->_('Encryption'),
            'required',
            false,
            'multiOptions',
            [
                Mail::MAIL_NO_ENCRYPT => $this->_('None'),
                Mail::MAIL_SSL => $this->_('SSL'),
                Mail::MAIL_TLS => $this->_('TLS')
            ]
        );
        $this->set(
            'gms_port',
            'label',
            $this->_('Port'),
            'required',
            true,
            'description',
            $this->_('Normal values: 25 for TLS and no encryption, 465 for SSL'),
            'validator',
            'Digits'
        );
        $this->set('gms_user', 'label', $this->_('User ID'), 'size', 20);

        Model::setChangeFieldsByPrefix($this, 'gms');
    }

    public function applyDetailSettings()
    {
        $this->set('gms_password',
            'label', $this->_('Password'),
            'elementClass', 'Password',
            'renderPassword', true,
            'repeatLabel', $this->_('Repeat password'),
            'description', $this->_('Enter new or remove stars to empty'));

        $type = new EncryptedField($this->valueEncryptor, true);
        $type->apply($this, 'gms_password');
    }
}