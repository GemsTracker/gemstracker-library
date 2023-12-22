<?php

/**
 *
 * @package    Gems
 * @subpackage Controller
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers;

use DateTimeImmutable;
use Gems\Model\LogModel;
use Gems\Model\Type\GemsDateTimeType;
use Gems\Repository\PeriodSelectRepository;
use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\Log\LogSearchSnippet;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 * Show the action log
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class LogHandler extends ModelSnippetLegacyHandlerAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $autofilterSnippets = ['Log\\LogTableSnippet'];

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStartSnippets = [
        ContentTitleSnippet::class,
        LogSearchSnippet::class,
        ];

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected array $showSnippets = ['Generic\\ContentTitleSnippet', 'Log\\LogShowSnippet'];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        protected PeriodSelectRepository $periodSelectRepository,
        protected LogModel $logModel
    )
    {
        parent::__construct($responder, $translate, $cache);
    }

    protected function createModel(bool $detailed, string $action): FullDataInterface
    {
        if ($detailed) {
            $this->logModel->applyDetailSettings();
        } else {
            $this->logModel->applyBrowseSettings();
        }

        return $this->logModel;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle(): string
    {
        return $this->_('Logging');
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

            $this->defaultSearchData = [
                'datefrom'         => $from,
                'dateuntil'        => $until,
                'dateused'         => 'gla_created',
            ];
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

        $type  = new GemsDateTimeType($this->translate);
        $where = $this->periodSelectRepository->createPeriodFilter($filter, $type->dateFormat, $type->storageFormat, $this->getSearchDefaults());
        if ($where) {
            $filter[] = $where;
        }

        return $filter;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic(int $count = 1): string
    {
        return $this->plural('log', 'log', $count);
    }
}