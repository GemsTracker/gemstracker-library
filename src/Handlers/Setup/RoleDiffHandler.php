<?php

namespace Gems\Handlers\Setup;

use Gems\ArrayDiffPresenter;
use Gems\Auth\Acl\AclRepository;
use Gems\Auth\Acl\ConfigRoleAdapter;
use Gems\Auth\Acl\DbRoleAdapter;
use Gems\Auth\Acl\RoleAdapterInterface;
use Gems\Layout\LayoutRenderer;
use Gems\Menu\RouteHelper;
use Gems\User\UserLoader;
use Gems\Util\Translated;
use Laminas\Diactoros\Response\HtmlResponse;
use MUtil\Ra;
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
class RoleDiffHandler extends \Gems\Handlers\ModelSnippetLegacyHandlerAbstract
{
    use RoleHandlerTrait;

    /**
     *
     * @var \Gems\Loader
     */
    public $loader;

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        private readonly RouteHelper $routeHelper,
        private readonly ConfigRoleAdapter $configRoleAdapter,
        private readonly DbRoleAdapter $dbRoleAdapter,
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
            $this->translateConfig($this->configRoleAdapter->getResolvedRoles()),
            $this->translateConfig($this->dbRoleAdapter->getResolvedRoles()),
            $this->_('Configuration in code'),
            $this->_('Configuration in database'),
        );

        return new HtmlResponse($this->layoutRenderer->renderTemplate('gems::role/diff', $this->request, [
            'diffTable' => $adp->format(),
        ]));
    }

    private function translateConfig(array $config): array
    {
        $translatedConfig = [];

        $existingPrivileges = $this->getUsedPrivileges();

        foreach ($config as $code => $role) {
            $translatedRole = [];

            $translatedRole[$this->_('Name')] = $role['grl_name'];
            $translatedRole[$this->_('Description')] = $role['grl_description'];
            $translatedRole[$this->_('Parents')] = array_values($role['grl_parents']);
            $translatedRole[$this->_('Privileges')] = $this->translatePrivileges($role['grl_privileges'], $existingPrivileges);
            $translatedRole[$this->_('Resolved privileges')] = $this->translatePrivileges($role[RoleAdapterInterface::ROLE_RESOLVED_PRIVILEGES], $existingPrivileges);

            $translatedConfig[$code] = $translatedRole;
        }

        return $translatedConfig;
    }

    private function translatePrivileges(array $privilegeList, array $existingPrivileges): array
    {
        $translated = Ra::filterKeys($existingPrivileges, $privilegeList);

        $missing = array_diff($privilegeList, array_keys($translated));

        foreach ($missing as $privilege) {
            $translated[$privilege] = $privilege;
        }

        return $translated;
    }

    protected function createModel(bool $detailed, string $action): DataReaderInterface
    {
        throw new \BadMethodCallException();
    }
}
