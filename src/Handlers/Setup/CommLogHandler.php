<?php

/**
 * Short description of file
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Setup;

use Gems\Handlers\ModelSnippetLegacyHandlerAbstract;
use Gems\Middleware\CurrentOrganizationMiddleware;
use Gems\Model\CommLogModel;
use Gems\Repository\PeriodSelectRepository;
use Gems\Snippets\AutosearchFormSnippet;
use DateTimeImmutable;
use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\Mail\Log\MailLogBrowseSnippet;
use Gems\Snippets\Mail\Log\MailLogSearchSnippet;
use Gems\User\Mask\MaskRepository;
use Zalt\Base\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 * Controller for looking at mail activity
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class CommLogHandler extends ModelSnippetLegacyHandlerAbstract
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
        'extraSort'   => [
            'grco_created' => SORT_DESC
        ],
    ];

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $autofilterSnippets = [
        MailLogBrowseSnippet::class,
        ];

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStartSnippets = [
        ContentTitleSnippet::class,
        MailLogSearchSnippet::class,
        ];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        protected MaskRepository $maskRepository,
        protected ProjectOverloader $overloader,
        protected PeriodSelectRepository $periodSelectRepository,
    )
    {
        parent::__construct($responder, $translate);
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
        /**
         * @var $model CommLogModel
         */
        $model = $this->overloader->create('Model\\CommLogModel');
        $model->setMaskRepository($this->maskRepository);
        $model->applySetting($detailed);
        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return string
     */
    public function getIndexTitle(): string
    {
        return $this->_('Mail Activity Log');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return string
     */
    public function getTopic(int $count = 1): string
    {
        return $this->plural('mail activity', 'mail activities', $count);
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
        if (! $this->defaultSearchData) {
            $from = new DateTimeImmutable('-14 days');
            $until = new DateTimeImmutable('+1 day');

            $currentOrganizationId = $this->request->getAttribute(CurrentOrganizationMiddleware::CURRENT_ORGANIZATION_ATTRIBUTE);

            $this->defaultSearchData = array(
                AutosearchFormSnippet::PERIOD_DATE_USED => 'grco_created',
                'grco_organization' => $currentOrganizationId,
                'datefrom'          => $from,
                'dateuntil'         => $until,
                );
        }

        return parent::getSearchDefaults();
    }

    /**
     * Get the filter to use with the model for searching
     *
     * @param boolean $useRequest Use the request as source (when false, the session is used)
     * @return array or false
     */
    public function getSearchFilter(bool $useRequest = true): array
    {
        $filter = parent::getSearchFilter($useRequest);

        $where = $this->periodSelectRepository->createPeriodFilter($filter);
        if ($where) {
            $filter[] = $where;
        }

        return $filter;
    }

    /**
     * Resend a log item
     */
    public function resendAction(): void
    {
        $this->addSnippets('Gems\\Snippets\\Communication\\ResendCommLogItemSnippet');        
    }
}
