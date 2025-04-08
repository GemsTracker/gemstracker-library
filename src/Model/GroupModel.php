<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model;

use Gems\Auth\Acl\AclRepository;
use Gems\Html;
use Gems\Repository\AccessRepository;
use Gems\Screens\ScreenLoader;
use Gems\SnippetsActions\ApplyLegacyActionInterface;
use Gems\SnippetsActions\ApplyLegacyActionTrait;
use Gems\User\UserLoader;
use Gems\Util\Translated;
use Gems\Validator\IPRanges;
use Zalt\Base\TranslatorInterface;
use Zalt\Late\Late;
use Zalt\Late\LateInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Sql\SqlRunnerInterface;
use Zalt\Model\Type\ConcatenatedType;
use Zalt\SnippetsActions\SnippetActionInterface;
use Zalt\Validator\Model\ModelUniqueValidator;

/**
 * @package    Gems
 * @subpackage Model
 * @since      Class available since version 1.0
 */
class GroupModel extends SqlTableModel implements ApplyLegacyActionInterface
{
    use ApplyLegacyActionTrait;

    public function __construct(
        protected readonly MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        protected readonly AccessRepository $accessRepository,
        protected readonly AclRepository $aclRepository,
        protected readonly ScreenLoader $screenLoader,
        protected readonly Translated $translatedUtil,
        protected readonly UserLoader $userLoader,
    )
    {
        parent::__construct('gems__groups', $metaModelLoader, $sqlRunner, $translate);

        $this->metaModelLoader->setChangeFields($this->metaModel, 'ggp');
        $this->applySettings();
    }

    public function applyAction(SnippetActionInterface $action): void
    {
        // Add id for excel export
        switch ($action->getSnippetAction()) {
            case 'export':
                $this->metaModel->set('ggp_id_group', [
                    'label' => 'id',
                ]);
                break;

            case 'edit':
                $this->metaModel->set('ggp_code', [
                    'readonly' => 'readonly',
                    'disabled' => 'disabled',
                ]);
        }

        if ($action->isDetailed()) {
            $html = Html::create('h4', $this->_('Screen settings'));
            $this->metaModel->set('screensettings', [
                'label' => ' ',
                'default' => $html,
                'elementClass' => 'Html',
                'order' => $this->metaModel->getOrder('ggp_respondent_browse') - 1,
                'value' => $html,
            ]);

            $this->metaModel->set('ggp_respondent_browse', [
                'label' => $this->_('Respondent browse screen'),
                'default' => 'Gems\\Screens\\Respondent\\Browse\\ProjectDefaultBrowse',
                'elementClass' => 'Radio',
                'multiOptions' => $this->screenLoader->listRespondentBrowseScreens()
            ]);
            /*
            $this->metaModel->set('ggp_respondent_edit', 'label', $this->_('Respondent edit screen'),
                'default', 'Gems\\Screens\\Respondent\\Edit\\ProjectDefaultEdit',
                'elementClass', 'Radio',
                'multiOptions', $this->screenLoader->listRespondentEditScreens()
            );
            $this->metaModel->set('ggp_respondent_show', 'label', $this->_('Respondent show screen'),
                'default', 'Gems\\Screens\\Respondent\\Show\\GemsProjectDefaultShow',
                'elementClass', 'Radio',
                'multiOptions', $this->screenLoader->listRespondentShowScreens()
            );*/
        }

        $this->metaModelLoader->addDatabaseTranslations($this->metaModel, $action->isDetailed());
    }

    public function applySettings(): void
    {
        $this->metaModel->set('ggp_name', [
            'label' => $this->_('Name'),
            'minlength' => 4,
            'size' => 15,
            'translate' => true,
            'validator' => ModelUniqueValidator::class,
        ]);
        $this->metaModel->set('ggp_code', [
            'label' => $this->_('Code'),
            'minlength' => 4,
            'size' => 15,
            'validator' => ModelUniqueValidator::class,
        ]);

        $this->metaModel->set('ggp_description', [
            'label' => $this->_('Description'),
            'size' => 40,
            'translate' => true,
        ]);
        $this->metaModel->set('ggp_role', [
            'label' => $this->_('Role'),
            'multiOptions' => $this->aclRepository->getRoleValues(),
        ]);

        $groups = $this->accessRepository->getGroups();
        unset($groups['']);
        $this->metaModel->set('ggp_may_set_groups', [
            'label' => $this->_('May set these groups'),
            'elementClass' => 'MultiCheckbox',
            'multiOptions' => $groups,
            MetaModelInterface::TYPE_ID => new ConcatenatedType(',', ', '),
        ]);

        $this->metaModel->set('ggp_default_group', [
            'label' => $this->_('Default group'),
            'description' => $this->_('Default group when creating new staff member'),
            'elementClass' => 'Select',
            'multiOptions' => $this->accessRepository->getGroups(),
        ]);

        $this->metaModel->set('ggp_member_type', [
            'label' => $this->_('Can be assigned to'),
            'elementClass' => 'Radio',
            'multiOptions' => $this->translatedUtil->getMemberTypes(),
        ]);

        $this->metaModel->set('ggp_allowed_ip_ranges', [
            'label' => $this->_('Login allowed from IP Ranges'),
            'description' => $this->_('Separate with | examples: 10.0.0.0-10.0.0.255, 10.10.*.*, 10.10.151.1 or 10.10.151.1/25'),
            'elementClass' => 'Textarea',
            'itemDisplay' => [$this, 'ipWrap'],
            'rows' => 4,
            'validator' => new IPRanges(),
        ]);
        $this->metaModel->setIfExists('ggp_no_2factor_ip_ranges', [
            'label' => $this->_('Two factor Optional IP Ranges'),
            'description' => $this->_('Separate with | examples: 10.0.0.0-10.0.0.255, 10.10.*.*, 10.10.151.1 or 10.10.151.1/25'),
            'default' => '127.0.0.1|::1',
            'elementClass' => 'Textarea',
            'itemDisplay' => [$this, 'ipWrap'],
            'rows' => 4,
            'validator' => new IPRanges(),
        ]);

        $this->metaModel->setIfExists('ggp_2factor_set', [
            'label' => $this->_('Login with two factor set'),
            'elementClass' => 'Radio',
            'multiOptions' => $this->userLoader->getGroupTwoFactorSetOptions(),
            'separator' => '<br/>',
        ]);
        $this->metaModel->setIfExists('ggp_2factor_not_set', [
            'label' => $this->_('Login without two factor set'),
            'elementClass' => 'Radio',
            'multiOptions' => $this->userLoader->getGroupTwoFactorNotSetOptions(),
            'separator' => '<br/>',
        ]);
        $yesNo = $this->translatedUtil->getYesNo();
        $this->metaModel->set('ggp_group_active', [
            'label' => $this->_('Active'),
            'elementClass' => 'Checkbox',
            'multiOptions' => $yesNo,
        ]);
    }

    /**
     *
     * @param ?string $value
     * @return LateInterface
     */
    public function ipWrap($value): LateInterface
    {
        return Late::call('str_replace', '|', ' | ', $value ?? '');
    }
}