<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\User;

use Gems\Db\ResultFetcher;
use Gems\Menu\MenuSnippetHelper;
use Gems\Repository\OrganizationRepository;
use Gems\Snippets\FormSnippetAbstract;
use Gems\User\Embed\EmbeddedUserData;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Html\UrlArrayAttribute;
use Zalt\Message\MessengerInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 * @package    Gems
 * @subpackage Snippets\User
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.8 14-Apr-2020 17:22:44
 */
class EmbeddedUserTestUrlForm extends FormSnippetAbstract
{
    /**
     *
     * @var \Gems\User\Embed\EmbeddedUserData
     */
    protected $_embedderData;

    /**
     * The form Id used for the save button
     *
     * If empty save button is not added
     *
     * @var string
     */
    protected $saveButtonId = '';

    /**
     *
     * @var \Gems\User\User
     */
    protected $selectedUser;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        MenuSnippetHelper $menuHelper,
        protected readonly OrganizationRepository $organizationRepository,
        protected readonly ResultFetcher $resultFetcher,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $menuHelper);
    }

    /**
     * Add the elements to the form
     *
     * @param \Zend_Form $form
     */
    protected function addFormElements(mixed $form)
    {
        $orgOptions = [
            'label'        => $this->_('Organization'),
            'multiOptions' => $this->selectedUser->getAllowedOrganizations(),
            'autoSubmit'   => true,
            ];

        $orgSelect = $form->createElement('Select', 'org_id', $orgOptions);
        $form->addElement($orgSelect);

        $userOptions = [
            'label'        => $this->_('Staff'),
            'description'  => $this->_('The Staff User to login as.'),
            'multiOptions' => $this->getStaffUsers($this->formData['org_id']),
            'autoSubmit'   => true,
            ];
        $userSelect = $form->createElement('Select', 'login_id', $userOptions);
        $form->addElement($userSelect);

        $pidOptions = [
            'label'        => $this->_('Respondent'),
            'description'  => $this->_('The respondent to login to.'),
            'multiOptions' => $this->getPatients($this->formData['org_id']),
            'autoSubmit'   => true,
            ];
        $pidSelect = $form->createElement('Select', 'pid', $pidOptions);
        $form->addElement($pidSelect);

        $urlOptions = [
            'label'       => $this->_('Example url'),
            'cols'        => 80,
            'description' => str_contains($this->formData['example_url'], '{') ?
                $this->_('Replace {} fields!') :
                $this->_('Please open in private mode or in other browser.'),
            'rows'        => 5,
            ];

        $url = $form->createElement('Textarea', 'example_url', $urlOptions);
        $form->addElement($url);
    }

    protected function addSaveButton(string $saveButtonId, string $saveLabel, string $buttonClass)
    {
        // No Button for this class
    }


    /**
     *
     * @return array
     */
    public function buildExampleEmbedUrl()
    {
        $auth = $this->_embedderData->getAuthenticator();

        $organization = $this->organizationRepository->getOrganization($this->formData['org_id']);

        $url[] =  $organization->getLoginUrl() . $this->menuHelper->getRouteUrl('embed.login');

        $url['epd'] = $this->selectedUser->getLoginName();
        $url['org'] = $this->formData['org_id'];
        $url['usr'] = $this->formData['login_id'];
        $url['pid'] = $this->formData['pid'];
        $url['key'] = $auth->getExampleKey($this->selectedUser);

        $url_string = '';
        foreach ($url as $key => $val) {
            $url_string .= "&$key=" . urlencode($val);
        }
        $url_string[0] = '?';

        // Return the {brackets} to not decoded as they should be changed by the user
        // (and will usally work fine in an url anyway).
        return str_replace(['%7B', '%7D'], ['{', '}'], UrlArrayAttribute::toUrlString($url));
    }

    /**
     * Return the default values for the form
     *
     * @return array
     */
    protected function getDefaultFormValues(): array
    {
        return [
            'org_id'    => $this->selectedUser->getBaseOrganizationId(),
            'login_id'  => '{login_id}',
            'pid'       => '{patient_nr}',
            'old_param' => 0,
            ];
    }

    /**
     *
     * @param int $orgId
     * @return array login_id => login_id
     */
    public function getPatients($orgId): array
    {
        $def = ['{patient_nr}' => '{patient_nr}'];
        $sql = "SELECT gr2o_patient_nr, gr2o_patient_nr
            FROM gems__respondent2org INNER JOIN gems__reception_codes ON gr2o_reception_code = grc_id_reception_code
            WHERE gr2o_id_organization = ? AND grc_success = 1 LIMIT 1000";

        $patients = $this->resultFetcher->fetchPairs($sql, [$orgId]);

        if ($patients) {
            return $def + $patients;
        }

        return $def;
    }

    /**
     *
     * @param int $orgId
     * @return array login_id => login_id
     */
    public function getStaffUsers($orgId): array
    {
        $def = ['{login_id}' => '{login_id}'];
        $sql = "SELECT gul_login, gul_login
            FROM gems__user_logins
            WHERE gul_id_organization = ? AND gul_user_class = ? AND gul_can_login = 1 AND 
                  gul_login  NOT IN (SELECT gsf_login FROM gems__staff INNER JOIN gems__systemuser_setup ON gsf_id_user = gsus_id_user)
            ORDER BY gul_login LIMIT 1000";

        $staff = $this->resultFetcher->fetchPairs($sql, [$orgId, $this->selectedUser->getUserDefinitionClass()]);

        if ($staff) {
            return $def + $staff;
        }

        return $def;
    }

    /**
     * Retrieve the header title to display
     *
     * @return string
     */
    protected function getTitle()
    {
        return $this->_('Example login url generator');
    }

    public function hasHtmlOutput(): bool
    {
        if ($this->selectedUser instanceof \Gems\User\User) {
            $this->_embedderData = $this->selectedUser->getEmbedderData();
            if ($this->selectedUser->isActive() && $this->_embedderData instanceof EmbeddedUserData) {
                return parent::hasHtmlOutput();
            }
        }

        return false;
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData(): array
    {
        parent::loadFormData();

        $this->formData['example_url'] = $this->buildExampleEmbedUrl();
        
        return $this->formData;
    }
}


