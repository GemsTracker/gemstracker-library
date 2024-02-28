<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Respondent;

use Gems\AuthNew\AuthenticationMiddleware;
use Gems\Handlers\ModelSnippetLegacyHandlerAbstract;
use Gems\Legacy\CurrentUserRepository;
use Gems\Model;
use Gems\Repository\RespondentRepository;
use Gems\Tracker\Respondent;
use Gems\User\User;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 5-mei-2015 13:15:49
 */
abstract class RespondentChildHandlerAbstract extends ModelSnippetLegacyHandlerAbstract
{
    use GetRespondentTrait;

    /**
     * Model level parameters used for all actions, overruled by any values set in any other
     * parameters array except the private $_defaultParamters values in this module.
     *
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $defaultParameters = array(
        'respondent' => 'getRespondent',
    );

    /**
     * The parameters used for the import action
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $importParameters = ['respondent' => null];

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStartSnippets = ['Generic\\ContentTitleSnippet', 'AutosearchInRespondentSnippet'];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        protected RespondentRepository $respondentRepository,
        protected CurrentUserRepository $currentUserRepository,
    ) {
        parent::__construct($responder, $translate, $cache);

        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

    protected function assertAccessFromOrganization(User $currentUser, int $organizationId): void
    {
        $this->getRespondent()->assertAccessFromOrganizationId($currentUser, $organizationId);
    }
}
