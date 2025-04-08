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

use Gems\Auth\Acl\ConfigGroupAdapter;
use Gems\Auth\Acl\GroupRepository;
use Gems\Html;
use Gems\Model\GroupModel;
use Psr\Cache\CacheItemPoolInterface;
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
     * The snippets used for the delete action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $deleteSnippets = ['Group\\GroupDeleteSnippet'];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        protected readonly GroupModel $groupModel,
        protected readonly GroupRepository $groupRepository,
    ) {
        parent::__construct($responder, $translate, $cache);
    }

    /**
     * Download the roles php config
     */
    public function downloadAction()
    {
        $phpFile = $this->groupRepository->buildGroupConfigFile();

        return new \Laminas\Diactoros\Response\TextResponse($phpFile, 200, [
            'content-type' => 'text/php',
            'content-length' => strlen($phpFile),
            'content-disposition' => 'inline; filename="groups.php"',
            'cache-control' => 'no-store',
        ]);
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
     * @return DataReaderInterface
     */
    public function createModel(bool $detailed, string $action): DataReaderInterface
    {
        $this->groupModel->applyStringAction($action, $detailed);
        return $this->groupModel;
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
        $br = Html::create('br');
        return [
            ['ggp_name', $br, 'ggp_code', $br, 'ggp_role'],
            ['ggp_description'],
            ['ggp_may_set_groups'],
            ['ggp_default_group', $br, 'ggp_group_active'],
            ['ggp_member_type'],
            ['ggp_allowed_ip_ranges'],
            ['ggp_no_2factor_ip_ranges'],
            ['ggp_2factor_not_set', $br, 'ggp_2factor_set'],
        ];
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return string
     */
    public function getIndexTitle(): string
    {
        $title = $this->_('Administrative groups');

        $groupAdapter = $this->groupRepository->groupAdapter;
        if ($groupAdapter instanceof ConfigGroupAdapter) {
            $title .= ' (' . $this->_('Defined on') . ' ' . $groupAdapter->getDefinitionDate()->format('d-m-Y H:i') . ')';
        }

        return $title;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return string
     */
    public function getTopic($count = 1): string
    {
        return $this->plural('group', 'groups', $count);
    }
}
