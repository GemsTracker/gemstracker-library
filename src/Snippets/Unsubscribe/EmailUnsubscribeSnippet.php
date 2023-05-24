<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Unsubscribe
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Unsubscribe;

use Gems\Legacy\CurrentUserRepository;
use Gems\Loader;
use Gems\Menu\MenuSnippetHelper;
use Gems\Snippets\FormSnippetAbstract;
use Gems\User\Organization;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Literal;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Where;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Message\MessengerInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Unsubscribe
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.4 19-Mar-2019 14:17:37
 */
class EmailUnsubscribeSnippet extends FormSnippetAbstract
{
    /**
     *
     * @var \Gems\User\Organization
     */
    protected $currentOrganization;

    /**
     * Since this forms acts as if it was successful when a valid e-mail address was
     * entered we need to store the real state for logging purposes here.
     *
     * @var boolean
     */
    protected $realChange = false;

    /**
     *
     * @var string Optional project specific after unsubscribe message.
     */
    protected $unsubscribedMessage;

    /**
     * @var int The value to assign while unsubscribing
     */
    protected $unsubscribedValue = 0;

    /**
     *
     * @var array of arrays, either null or respondent id and org id
     */
    protected $userData = [0 => []];

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        protected Loader $loader,
        protected MenuSnippetHelper $menuHelper,
        protected readonly Adapter $db,
        protected readonly CurrentUserRepository $currentUserRepository,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $menuHelper);

        $this->currentUser = $currentUserRepository->getCurrentUser();
        $this->currentOrganization = $this->currentUser->getCurrentOrganization();
    }

    /**
     * Add the elements to the form
     *
     * @param mixed $form
     */
    protected function addFormElements(mixed $form)
    {
//        \MUtil\EchoOut\EchoOut::track('EmailUnsubscribeSnippet');
        // Veld inlognaam
        $element = $form->createElement('text', 'email');
        $element->setLabel($this->_('Your E-Mail address'))
                ->setAttrib('size', 30)
                ->setRequired(true)
                ->addValidator('SimpleEmail');
                // FIXME Roel
//                ->addValidator($this->loader->getSubscriptionThrottleValidator());

        $form->addElement($element);

        return $element;
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();
        
        // Csrf may be set by project setting in parent
        $this->useCsrf = false;
    }
    
    /**
     * Hook that allows actions when data was saved
     *
     * When not rerouted, the form will be populated afterwards
     *
     * @param int $changed The number of changed rows (0 or 1 usually, but can be more)
     */
    protected function afterSave($changed)
    {
        // \MUtil\EchoOut\EchoOut::track($this->getMessenger()->getCurrentMessages(), $this->formData, $this->userData, $this->realChange);
        /*
        // This is also commented in our parent class (FormSnippetAbstract)
        foreach ($this->userData as $userData) {
            $this->accesslogRepository->logRequest(
                    $this->requestInfo,
                    $this->getMessages(),
                    $this->formData + $userData,
                    isset($userData['gr2o_id_user']) ? $userData['gr2o_id_user'] : 0,
                    $this->realChange);
        }
        */
    }

    /**
     * Hook containing the actual save code.
     *
     * @return int The number of "row level" items changed
     */
    protected function saveData(): int
    {
        $this->addMessage($this->unsubscribedMessage ?:
                $this->_('If your E-email address is known, you have been unsubscribed.'));

        $sql = new Sql($this->db);
        $select = $sql->select()
            ->columns([
                'gr2o_patient_nr',
                'gr2o_id_organization',
                'gr2o_id_user',
                'gr2o_mailable',
            ])
            ->from('gems__respondent2org')
            ->where([
                'gr2o_email' => $this->formData['email'],
                'gr2o_id_organization' => $this->currentOrganization->getId(),
            ]);

        $resultSet = $sql->prepareStatementForSqlObject($select)->execute();

        // \MUtil\EchoOut\EchoOut::track($rows);
        foreach ($resultSet as $id => $row) {
            // Save respondent & ord id
            $this->userData[$id]['gr2o_id_user']         = $row['gr2o_id_user'];
            $this->userData[$id]['gr2o_id_organization'] = $this->currentOrganization->getId();

            if ($row['gr2o_mailable']) {
                $values = [
                    'gr2o_mailable' => $this->unsubscribedValue,
                    'gr2o_changed' => new Literal('CURRENT_TIMESTAMP'),
                    'gr2o_changed_by' => $row['gr2o_id_user'],
                ];

                $update = $sql->update('gems__respondent2org')
                    ->where([
                        'gr2o_email' => $this->formData['email'],
                        'gr2o_id_organization' => $this->currentOrganization->getId(),
                    ])->where(function (Where $where) {
                        $where->notEqualTo('gr2o_mailable', $this->unsubscribedValue);
                    })->set($values);
                $sql->prepareStatementForSqlObject($update)->execute();

                // Signal something has actually changed for logging purposes
                $this->realChange = true;
            }
        }

        // Always act like something was saved when
        return 1;
    }
}
