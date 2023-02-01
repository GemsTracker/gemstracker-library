<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Setup;

use Gems\Handlers\LogHandler;
use Gems\User\User;
use Gems\User\UserLoader;
use Laminas\Db\Adapter\Adapter;
use Gems\Model;
use MUtil\Model\ModelAbstract;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 16-apr-2015 17:36:20
 */
class StaffLogHandler extends LogHandler
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
    protected array $autofilterParameters = ['extraFilter' => 'getStaffFilter'];

    /**
     *
     * @var \Gems\User\User
     */
    public $currentUser;

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStartSnippets = ['Generic\\ContentTitleSnippet', 'Log\\StaffLogSearchSnippet'];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        Model $modelLoader,
        Adapter $db,
        protected UserLoader $userLoader,
    ) {
        parent::__construct($responder, $translate, $modelLoader, $db);
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
    protected function createModel(bool $detailed, string $action): ModelAbstract
    {
        // Make sure the user is loaded
        $user = $this->getSelectedUser();

        if ($user) {
            if (! ($this->currentUser->hasPrivilege('pr.staff.see.all') ||
                    $this->currentUser->isAllowedOrganization($user->getBaseOrganizationId()))) {
                throw new \Gems\Exception($this->_('No access to page'), 403, null, sprintf(
                        $this->_('You have no right to access users from the organization %s.'),
                        $user->getBaseOrganization()->getName()
                        ));
            }
        }

        return $this->modelLoader->getStaffLogModel($detailed);
    }

    /**
     * Function to allow the creation of search defaults in code
     *
     * @see getSearchFilter()
     *
     * @return array
     */
    public function getSearchDefaults(): array
    {
        $data = parent::getSearchDefaults();

        if (! isset($data[\MUtil\Model::REQUEST_ID])) {
            $data[\MUtil\Model::REQUEST_ID] = intval($this->_getIdParam());
        }

        return $data;
    }

    /**
     * Load the user selected by the request - if any
     *
     * @staticvar \Gems\User\User $user
     * @return User or false when not available
     */
    public function getSelectedUser(): User|bool
    {
        static $user = null;

        if ($user !== null) {
            return $user;
        }

        $staffId = $this->_getIdParam();
        if ($staffId) {
            $user   = $this->userLoader->getUserByStaffId($staffId);
            return $user;
        }
        return false;
    }

    /**
     * Get filter for current respondent
     *
     * @return array
     */
    public function getStaffFilter(): array
    {
        return ['gla_by' => intval($this->_getIdParam())];
    }
}
