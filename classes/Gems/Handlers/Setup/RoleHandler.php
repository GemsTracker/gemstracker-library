<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Michiel Rook
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Setup;

use Gems\Auth\Acl\AclRepository;
use Gems\Auth\Acl\RoleAdapterInterface;
use Gems\MenuNew\Menu;
use Gems\MenuNew\RouteHelper;
use Gems\Middleware\FlashMessageMiddleware;
use Gems\Middleware\MenuMiddleware;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use MUtil\Model\ModelAbstract;
use MUtil\Model\NestedArrayModel;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Message\MessageStatus;
use Zalt\Message\StatusMessengerInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 * @package    Gems
 * @subpackage Handlers\Setup
 * @since      Class available since version 1.9.2
 */
class RoleHandler extends \Gems\Handlers\ModelSnippetLegacyHandlerAbstract
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
    protected array $autofilterParameters = array(
        'extraSort'   => array(
            'grl_name' => SORT_ASC,
        ),
    );

    /**
     * Tags for cache cleanup after changes, passed to snippets
     *
     * @var array
     */
    public array $cacheTags = array('gems_acl', 'roles', 'group', 'groups');

    /**
     * The parameters used for the create and edit actions.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $createEditParameters = array(
        'usedPrivileges' => 'getUsedPrivileges',
    );

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    protected array $createEditSnippets = ['Role\\RoleEditFormSnippet'];

    /**
     *
     * @var array
     */
    protected $usedPrivileges;

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        private readonly RouteHelper $routeHelper,
        private readonly AclRepository $aclRepository,
        private readonly UrlHelper $urlHelper,
    ) {
        parent::__construct($responder, $translate);
    }

    /**
     * Helper function to show a table
     *
     * @param string $caption
     * @param array $data
     * @param boolean $nested
     */
    protected function _showTable($caption, $data, $nested = false)
    {
        $table = \Zalt\Html\TableElement::createArray($data, $caption, $nested);
        $table->class = 'browser table';
        $div = \Zalt\Html\Html::create()->div(array('class' => 'table-container'));
        $div[] = $table;
        $this->html[] = $div;
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
    public function createModel($detailed, $action): ModelAbstract
    {
        if ($this->aclRepository->hasRolesFromConfig()) {
            $roles = array_values($this->aclRepository->getResolvedRoles());
            foreach ($roles as $i => $role) {
                if ($detailed && 'show' === $action) {
                    $roles[$i]['inherited'] = $role['grl_parents'];
                    $roles[$i]['not_allowed'] = implode(',', $role['grl_parents'] ?? []) . "\t" . implode(',', $role['grl_privileges'] ?? []);
                }
            }
            $model = new NestedArrayModel('gems__roles', $roles);
            $model->setKeys([\MUtil\Model::REQUEST_ID => 'grl_name']);
        } else {
            $model = new \MUtil\Model\TableModel('gems__roles');

            $id = $this->request->getAttribute(\MUtil\Model::REQUEST_ID);
            if ($id !== null && !ctype_digit((string)$id)) {
                throw new \Exception();
            }
        }

        $model->set('grl_name', 'label', $this->_('Name'),
                'size', 15,
                'minlength', 4
                );
        $model->set('grl_description', 'label', $this->_('Description'),
                'size', 40);
        $model->set('grl_parents', 'label', $this->_('Parents'));

        $tpa = new \MUtil\Model\Type\ConcatenatedRow(',', ', ');
        $tpa->apply($model, 'grl_parents');
        $model->setOnLoad('grl_parents', fn ($parents) => $this->aclRepository->convertKeysToNames($parents, true));

        $model->set('grl_privileges', 'label', $this->_('Privileges'));
        $tpr = new \MUtil\Model\Type\ConcatenatedRow(',', '<br/>');
        $tpr->apply($model, 'grl_privileges');

        if ($detailed) {
            $model->set('grl_name',
                    'validators[unique]', $model->createUniqueValidator('grl_name'),
                    'validators[nomaster]', new \MUtil\Validate\IsNot(
                            'master',
                            $this->_('The name "master" is reserved')
                            )
                    );

            $model->set('grl_privileges', 'formatFunction', array($this, 'formatPrivileges'));

            if ('show' === $action) {
                if (!$this->aclRepository->hasRolesFromConfig()) {
                    $model->addColumn('grl_parents', 'inherited');
                }
                $tpa->apply($model, 'inherited');
                $model->set('inherited',
                        'label', $this->_('Inherited privileges'),
                        'formatFunction', array($this, 'formatInherited'));
                $model->setOnLoad('inherited', [$this->aclRepository, 'convertKeysToNames']);

                // Concatenated field, we can not use onload so handle translation to role names in the formatFunction
                if (!$this->aclRepository->hasRolesFromConfig()) {
                    $model->addColumn("CONCAT(COALESCE(grl_parents, ''), '\t', COALESCE(grl_privileges, ''))", 'not_allowed');
                }
                $model->set('not_allowed',
                        'label', $this->_('Not allowed'),
                        'formatFunction', array($this, 'formatNotAllowed'));
            }
        } else {
            $model->set('grl_privileges', 'formatFunction', array($this, 'formatLongLine'));
        }

        if (!$this->aclRepository->hasRolesFromConfig()) {
            \Gems\Model::setChangeFieldsByPrefix($model, 'grl');
        }

        return $model;
    }

    /**
     * Action for showing a edit item page with extra title
     */
    public function editAction()
    {
        $model   = $this->getModel();

        $id = $this->request->getAttribute(\MUtil\Model::REQUEST_ID);
        $data = $model->loadFirst(['grl_id_role' => $id]);

        //If we try to edit master, add an error message and reroute
        if (isset($data['grl_name']) && $data['grl_name']=='master') {
            $this->addMessage($this->_('Editing `master` is not allowed'));
            $this->_reroute(array('action'=>'index'), true);
        }

        parent::editAction();
    }

    public function deleteAction()
    {
        $model   = $this->getModel();

        $id = $this->request->getAttribute(\MUtil\Model::REQUEST_ID);
        $data = $model->loadFirst(['grl_id_role' => $id]);

        $children = $this->aclRepository->getChildren($data['grl_name']);

        // If we try to delete a parent, add an error message and reroute
        if (count($children) > 0) {
            /**
             * @var $messenger StatusMessengerInterface
             */
            $messenger = $this->request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);
            $messenger->addMessage(sprintf(
                $this->_('This role is being used as a parent of roles %s and hence cannot be deleted'),
                implode(', ', $children)
            ), MessageStatus::Danger);

            return new RedirectResponse($this->urlHelper->generate('setup.access.roles.show', [
                \MUtil\Model::REQUEST_ID => $id,
            ]));
        }

        parent::deleteAction();
    }

    /**
     * Output for browsing rols
     *
     * @param array $privileges
     * @return array
     */
    public function formatLongLine(array $privileges)
    {
        $output     = \Zalt\Html\Html::create('div');

        if (count($privileges)) {
            $privileges = array_combine($privileges, $privileges);
            foreach ($this->getUsedPrivileges() as $privilege => $description) {
                if (isset($privileges[$privilege])) {
                    if (count($output) > 11) {
                        $output->append('...');
                        return $output;
                    }
                    if (\MUtil\StringUtil\StringUtil::contains($description, '<br/>')) {
                        $description = substr($description, 0, strpos($description, '<br/>'));
                    }
                    $output->raw($description);
                    $output->br();
                }
            }
        }

        return $output;
    }

    /**
     * Output of not allowed for viewing rols
     *
     * @param array $parent
     * @return \Zalt\Html\ListElement
     */
    public function formatInherited(array $parents)
    {
        $privileges = array_keys($this->getInheritedPrivileges($parents));
        return $this->formatPrivileges($privileges);
    }

    /**
     * Output of not allowed for viewing rols
     *
     * @param strong $data parents tab privileges
     * @return \Zalt\Html\ListElement
     */
    public function formatNotAllowed($data)
    {
        list($parents_string, $privileges_string) = explode("\t", $data, 2);
        $parents    = strlen($parents_string) > 0 ? explode(',', $parents_string) : [];
        $privileges = strlen($privileges_string) > 0 ? explode(',', $privileges_string) : [];
        if (count($privileges) > 0 ) {
            $privileges = array_combine($privileges, $privileges);
        }

        // Concatenated field, we can not use onload so handle translation here
        $parents = $this->aclRepository->convertKeysToNames($parents);

        $notAllowed = $this->getUsedPrivileges();
        $notAllowed = array_diff_key($notAllowed, $this->getInheritedPrivileges($parents), $privileges);

        $output = $this->formatPrivileges(array_keys($notAllowed));
        $output->class = 'notallowed deleted';

        return $output;
    }

    /**
     * Output for viewing rols
     *
     * @param array $privileges
     * @return \Zalt\Html\HtmlElement
     */
    public function formatPrivileges(array $privileges)
    {
        if (count($privileges)) {
            $output     = \Zalt\Html\ListElement::ul();
            $privileges = array_combine($privileges, $privileges);

            $output->class = 'allowed';

            foreach ($this->getUsedPrivileges() as $privilege => $description) {
                if (isset($privileges[$privilege])) {
                    $output->li()->raw($description);
                }
            }
            if (count($output)) {
                return $output;
            }
        }

        return \Zalt\Html\Html::create('em', $this->_('No privileges found.'));
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle(): string
    {
        return $this->_('Administrative roles');
    }

    /**
     * Get the privileges for thess parents
     *
     * @param array $parents
     * @return array privilege => setting
     */
    protected function getInheritedPrivileges(array $parents)
    {
        if (! $parents) {
            return array();
        }

        $rolePrivileges = $this->aclRepository->getResolvedRoles();
        $inherited      = array();
        foreach ($parents as $parent) {
            if (isset($rolePrivileges[$parent])) {
                $inherited = $inherited + array_flip($rolePrivileges[$parent][RoleAdapterInterface::ROLE_RESOLVED_PRIVILEGES]);
            }
        }
        // Sneaks in:
        unset($inherited[""]);

        return $inherited;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1): string
    {
        return $this->plural('role', 'roles', $count);
    }

    /**
     * Get the privileges a role can have.
     *
     * @return array
     */
    protected function getUsedPrivileges()
    {
        if (! $this->usedPrivileges) {
            /** @var Menu $menu */
            $menu = $this->request->getAttribute(MenuMiddleware::MENU_ATTRIBUTE);
            $routeLabelsByPrivilege = $menu->getRouteLabelsByPrivilege();
            $privileges = $this->routeHelper->getAllRoutePrivileges();
            $supplementaryPrivileges = $this->aclRepository->getSupplementaryPrivileges();

            $privilegeNames = [];
            foreach ($routeLabelsByPrivilege as $privilege => $labels) {
                $privilegeNames[$privilege] = implode("<br/>&nbsp; + ", $labels);
            }

            foreach ($privileges as $privilege) {
                if (!isset($privilegeNames[$privilege])) {
                    $privilegeNames[$privilege] = $privilege;
                }
            }

            foreach ($supplementaryPrivileges as $privilege => $label) {
                if (!isset($privilegeNames[$privilege])) {
                    $privilegeNames[$privilege] = $label->trans($this->translate);
                }
            }

            asort($privilegeNames);
            //don't allow to edit the pr.nologin and pr.islogin privilege
            unset($privilegeNames['pr.nologin']);
            unset($privilegeNames['pr.islogin']);

            $this->usedPrivileges = $privilegeNames;
        }

        return $this->usedPrivileges;
    }

    /**
     * Action to shw overview of all privileges
     */
    public function overviewAction()
    {
        $roles = array();

        foreach ($this->aclRepository->getResolvedRoles() as $roleName => $roleConfig) {
            $roles[$roleName][$this->_('Role')]    = $roleName;
            $roles[$roleName][$this->_('Parents')] = $roleConfig[RoleAdapterInterface::ROLE_PARENTS]   ? implode(', ', $roleConfig[RoleAdapterInterface::ROLE_PARENTS])   : null;
            $roles[$roleName][$this->_('Allowed')] = $roleConfig[RoleAdapterInterface::ROLE_ASSIGNED_PRIVILEGES] ? implode(', ', $roleConfig[RoleAdapterInterface::ROLE_ASSIGNED_PRIVILEGES]) : null;
            //$roles[$role][$this->_('Denied')]  = $privileges[\Zend_Acl::TYPE_DENY]  ? implode(', ', $privileges[\Zend_Acl::TYPE_DENY])  : null;
            $roles[$roleName][$this->_('Inherited')] = $roleConfig[RoleAdapterInterface::ROLE_INHERITED_PRIVILEGES] ? implode(', ', $roleConfig[RoleAdapterInterface::ROLE_INHERITED_PRIVILEGES]) : null;
            //$roles[$role][$this->_('Parent denied')]  = $privileges[\MUtil\Acl::INHERITED][\Zend_Acl::TYPE_DENY]  ? implode(', ', $privileges[\MUtil\Acl::INHERITED][\Zend_Acl::TYPE_DENY])  : null;
        }
        ksort($roles);

        $this->html->h2($this->_('Project role overview'));

        $this->_showTable($this->_('Roles'), $roles, true);
    }

    /**
     * Action to show all privileges
     */
    public function privilegeAction()
    {
        $privileges = array();

        foreach ($this->acl->getPrivilegeRoles() as $privilege => $roles) {
            $privileges[$privilege][$this->_('Privilege')] = $privilege;
            $privileges[$privilege][$this->_('Allowed')]   = $roles[\Zend_Acl::TYPE_ALLOW] ? implode(', ', $roles[\Zend_Acl::TYPE_ALLOW]) : null;
            $privileges[$privilege][$this->_('Denied')]    = $roles[\Zend_Acl::TYPE_DENY]  ? implode(', ', $roles[\Zend_Acl::TYPE_DENY])  : null;
        }

        // Add unassigned rights to the array too
        $all_existing = $this->getUsedPrivileges();
        $unassigned   = array_diff_key($all_existing, $privileges);
        $nonexistent  = array_diff_key($privileges, $all_existing);
        unset($nonexistent['pr.nologin']);
        unset($nonexistent['pr.islogin']);
        ksort($nonexistent);

        foreach ($unassigned as $privilege => $description) {
            $privileges[$privilege] = array(
                $this->_('Privilege') => $privilege,
                $this->_('Allowed')   => null,
                $this->_('Denied')    => null
            );
        }
        ksort($privileges);

        $this->html->h2($this->_('Project privileges'));
        $this->_showTable($this->_('Privileges'), $privileges, true);

        // Nonexistent rights are probably left-overs from old installations, this should be cleaned
        if (!empty($nonexistent)) {
            $this->_showTable($this->_('Assigned but nonexistent privileges'), $nonexistent, true);
        }
        // $this->acl->echoRules();
    }
}