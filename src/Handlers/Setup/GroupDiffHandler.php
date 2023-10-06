<?php

namespace Gems\Handlers\Setup;

use Gems\ArrayDiffPresenter;
use Gems\Auth\Acl\AclRepository;
use Gems\Auth\Acl\ConfigGroupAdapter;
use Gems\Auth\Acl\DbGroupAdapter;
use Gems\Layout\LayoutRenderer;
use Gems\User\UserLoader;
use Gems\Util\Translated;
use Laminas\Diactoros\Response\HtmlResponse;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Data\DataReaderInterface;
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
class GroupDiffHandler extends \Gems\Handlers\ModelSnippetLegacyHandlerAbstract
{
    /**
     *
     * @var \Gems\Loader
     */
    public $loader;

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        private readonly ConfigGroupAdapter $configGroupAdapter,
        private readonly DbGroupAdapter $dbGroupAdapter,
        private readonly LayoutRenderer $layoutRenderer,
        private readonly AclRepository $aclRepository,
        private readonly Translated $translatedUtil,
        private readonly UserLoader $userLoader,
    ) {
        parent::__construct($responder, $translate);
    }

    /**
     * View the roles diff
     */
    public function diffAction()
    {
        $adp = new ArrayDiffPresenter(
            $this->translateConfig($this->configGroupAdapter->getGroupsConfig()),
            $this->translateConfig($this->dbGroupAdapter->getGroupsConfig()),
            $this->_('Configuration in code'),
            $this->_('Configuration in database'),
        );

        return new HtmlResponse($this->layoutRenderer->renderTemplate('gems::group/diff', $this->request, [
            'diffTable' => $adp->format(),
        ]));
    }

    private function translateConfig(array $config): array
    {
        $translatedConfig = [];

        $roleValues = $this->aclRepository->getRoleValues();
        $memberTypes = $this->translatedUtil->getMemberTypes();
        $yesNo = $this->translatedUtil->getYesNo();
        $twoFactorSetOptions = $this->userLoader->getGroupTwoFactorSetOptions();
        $twoFactorNotSetOptions = $this->userLoader->getGroupTwoFactorNotSetOptions();

        $getGroupName = fn (?string $code) => ($code === null) ? '' : $config[$code]['ggp_name'];

        foreach ($config as $code => $group) {
            $translatedGroup = [];

            $translatedGroup[$this->_('Code')] = $group['ggp_code'];
            $translatedGroup[$this->_('Name')] = $group['ggp_name'];
            $translatedGroup[$this->_('Description')] = $group['ggp_description'];
            $translatedGroup[$this->_('Role')] = $roleValues[$group['ggp_role']];
            $translatedGroup[$this->_('May set these groups')] = array_map($getGroupName, $group['ggp_may_set_groups']);
            $translatedGroup[$this->_('Default group')] = $getGroupName($group['ggp_default_group']);
            $translatedGroup[$this->_('Active')] = $yesNo[$group['ggp_group_active']];
            $translatedGroup[$this->_('Can be assigned to')] = $memberTypes[$group['ggp_member_type']];
            $translatedGroup[$this->_('Login allowed from IP Ranges')] = $group['ggp_allowed_ip_ranges'];
            $translatedGroup[$this->_('Two factor Optional IP Ranges')] = $group['ggp_no_2factor_ip_ranges'];
            $translatedGroup[$this->_('Login with two factor set')] = $twoFactorSetOptions[$group['ggp_2factor_set']];
            $translatedGroup[$this->_('Login without two factor set')] = $twoFactorNotSetOptions[$group['ggp_2factor_not_set']];

            $translatedConfig[$code] = $translatedGroup;
        }

        return $translatedConfig;
    }

    protected function createModel(bool $detailed, string $action): DataReaderInterface
    {
        throw new \BadMethodCallException();
    }
}
