<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Setup;

use Gems\Auth\Acl\AclRepository;
use Gems\Repository\AccessRepository;
use Gems\Roles;
use Gems\User\UserLoader;
use Gems\Util\Translated;
use MUtil\Model\ModelAbstract;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class GroupHandler extends \Gems\Handlers\ModelSnippetLegacyHandlerAbstract
{
    /**
     * The parameters used for the autofilter action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $autofilterParameters = [
        'columns'     => 'getBrowseColumns',
        'extraSort'   => [
            'ggp_name' => SORT_ASC,
        ],
    ];

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public array $cacheTags = ['group', 'groups'];

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    protected array $createEditSnippets = ['Group\\GroupFormSnippet'];

    /**
     *
     * @var \Gems\User\User
     */
    public $currentUser;

    /**
     * The snippets used for the delete action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $deleteSnippets = ['Group\\GroupDeleteSnippet'];

    /**
     *
     * @var \Gems\Loader
     */
    public $loader;

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        private readonly Roles $roles,
        private readonly UserLoader $userLoader,
        private readonly AclRepository $aclRepository,
        private readonly AccessRepository $accessRepository,
        private readonly Translated $translatedUtil,
    ) {
        parent::__construct($responder, $translate);
    }

    /**
     *
     * @throws \Exception
     */
    public function changeUiAction()
    {
        if (! $this->currentUser->hasPrivilege('pr.group.switch', false)) {
            $this->escort->setError(
                $this->_('No access to page'),
                403,
                sprintf($this->_('Access to the %s/%s page is not allowed for your current group: %s.'),
                    $this->requestHelper->getControllerName(),
                    $this->requestHelper->getActionName(),
                    $this->currentUser->getGroup()->getName()),
                true);
        }

        $group = null;
        $url = null;
        $queryParams = $this->request->getQueryParams();
        if (isset($queryParams['group'])) {
            $group = strtolower($queryParams['group']);
        }
        if (isset($queryParams['current_uri'])) {
            $url = strtolower($queryParams['current_uri']);
        }

        if ((! $url) || ('/' !== $url[0])) {
            throw new \Exception($this->_('Illegal group redirect url.'));
        }

        // Throws exception on invalid value
        $this->currentUser->setGroupTemp(intval($group));

        if ($url) {
            $this->redirectUrl = $url;
        } else {
            $this->redirectUrl = '/';
        }
    }

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil\Model\ModelAbstract
     */
    public function createModel(bool $detailed, string $action): ModelAbstract
    {
        $model = new \MUtil\Model\TableModel('gems__groups');

        // Add id for excel export
        if ($action == 'export') {
            $model->set('ggp_id_group', 'label', 'id');
        }

        $model->set('ggp_name', [
            'label' => $this->_('Name'),
            'minlength' => 4,
            'size' => 15,
            'translate' => true,
            'validator' => $model->createUniqueValidator('ggp_name'),
        ]);
        $model->set('ggp_description', [
            'label' => $this->_('Description'),
            'size' => 40,
            'translate' => true,
        ]);
        $model->set('ggp_role', [
            'label' => $this->_('Role'),
            'multiOptions' => $this->aclRepository->getRoleValues(),
        ]);
        $model->setOnLoad('ggp_role', [$this->roles, 'translateToRoleName']);
        $model->setOnSave('ggp_role', [$this->roles, 'translateToRoleId']);

        $groups = $this->accessRepository->getGroups();
        unset($groups['']);
        $model->set('ggp_may_set_groups', [
            'label' => $this->_('May set these groups'),
            'elementClass' => 'MultiCheckbox',
            'multiOptions' => $groups,
        ]);
        $tpa = new \MUtil\Model\Type\ConcatenatedRow(',', ', ');
        $tpa->apply($model, 'ggp_may_set_groups');

        $model->set('ggp_default_group', [
            'label' => $this->_('Default group'),
            'description' => $this->_('Default group when creating new staff member'),
            'elementClass' => 'Select',
            'multiOptions' => $this->accessRepository->getGroups(),
        ]);

        $yesNo = $this->translatedUtil->getYesNo();
        $model->set('ggp_staff_members', [
            'label' => $this->_('Staff'),
            'elementClass' => 'Checkbox',
            'multiOptions' => $yesNo,
        ]);
        $model->set('ggp_respondent_members', [
            'label' => $this->_('Respondents'),
            'elementClass' => 'Checkbox',
            'multiOptions' => $yesNo,
        ]);
        $model->set('ggp_allowed_ip_ranges', [
            'label' => $this->_('Login allowed from IP Ranges'),
            'description' => $this->_('Separate with | examples: 10.0.0.0-10.0.0.255, 10.10.*.*, 10.10.151.1 or 10.10.151.1/25'),
            'elementClass' => 'Textarea',
            'itemDisplay' => [$this, 'ipWrap'],
            'rows' => 4,
            'validator' => new \Gems\Validate\IPRanges(),
        ]);
        $model->setIfExists('ggp_no_2factor_ip_ranges', [
            'label' => $this->_('Two factor Optional IP Ranges'),
            'description' => $this->_('Separate with | examples: 10.0.0.0-10.0.0.255, 10.10.*.*, 10.10.151.1 or 10.10.151.1/25'),
            'default' => '127.0.0.1|::1',
            'elementClass' => 'Textarea',
            'itemDisplay' => [$this, 'ipWrap'],
            'rows' => 4,
            'validator' => new \Gems\Validate\IPRanges(),
        ]);

        $model->setIfExists('ggp_2factor_set', [
            'label' => $this->_('Login with two factor set'),
            'elementClass' => 'Radio',
            'multiOptions' => $this->userLoader->getGroupTwoFactorSetOptions(),
            'separator' => '<br/>',
        ]);
        $model->setIfExists('ggp_2factor_not_set', [
            'label' => $this->_('Login without two factor set'),
            'elementClass' => 'Radio',
            'multiOptions' => $this->userLoader->getGroupTwoFactorNotSetOptions(),
            'separator' => '<br/>',
        ]);
        $model->set('ggp_group_active', [
            'label' => $this->_('Active'),
            'elementClass' => 'Checkbox',
            'multiOptions' => $yesNo,
        ]);

        if ($detailed) {
            /*$html = \MUtil\Html::create()->h4($this->_('Screen settings'));
            $model->set('screensettings', 'label', ' ',
                'default', $html,
                'elementClass', 'Html',
                'value', $html
            );

            $model->set('ggp_respondent_browse', 'label', $this->_('Respondent browse screen'),
                'default', 'Gems\\Screens\\Respondent\\Browse\\ProjectDefaultBrowse',
                'elementClass', 'Radio',
                'multiOptions', $this->screenLoader->listRespondentBrowseScreens()
            );
            $model->set('ggp_respondent_edit', 'label', $this->_('Respondent edit screen'),
                'default', 'Gems\\Screens\\Respondent\\Edit\\ProjectDefaultEdit',
                'elementClass', 'Radio',
                'multiOptions', $this->screenLoader->listRespondentEditScreens()
            );
            $model->set('ggp_respondent_show', 'label', $this->_('Respondent show screen'),
                'default', 'Gems\\Screens\\Respondent\\Show\\GemsProjectDefaultShow',
                'elementClass', 'Radio',
                'multiOptions', $this->screenLoader->listRespondentShowScreens()
            );*/

            //$this->maskStore->addMaskSettingsToModel($model, 'ggp_mask_settings');
        }

        if (isset($this->config['translate'], $this->config['translate']['databaseFields']) && $this->config['translate']['databaseFields'] === true) {
            if ('create' == $action || 'edit' == $action) {
                $this->loader->getModels()->addDatabaseTranslationEditFields($model);
            } else {
                $this->loader->getModels()->addDatabaseTranslations($model);
            }
        }

        \Gems\Model::setChangeFieldsByPrefix($model, 'ggp');

        return $model;
    }

    /**
     * Set column usage to use for the browser.
     *
     * Must be an array of arrays containing the input for TableBridge->setMultisort()
     *
     * @return array or false
     */
    public function getBrowseColumns(): bool|array
    {
        $br = \Zalt\Html\Html::create('br');
        return [
            ['ggp_name', $br, 'ggp_role'],
            ['ggp_description'],
            ['ggp_may_set_groups'],
            ['ggp_default_group', $br, 'ggp_group_active'],
            ['ggp_staff_members', $br, 'ggp_respondent_members'],
            ['ggp_allowed_ip_ranges'],
            ['ggp_no_2factor_ip_ranges'],
            ['ggp_2factor_not_set', $br, 'ggp_2factor_set'],
        ];
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle(): string
    {
        return $this->_('Administrative groups');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1): string
    {
        return $this->plural('group', 'groups', $count);
    }

    /**
     *
     * @param string $value
     * @return string
     */
    public function ipWrap($value)
    {
        return \MUtil\Lazy::call('str_replace', '|', ' | ', $value ?? '');
    }
}
