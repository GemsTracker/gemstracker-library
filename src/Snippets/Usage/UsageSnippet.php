<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets\Usage;

use Gems\Menu\MenuSnippetHelper;
use Gems\Usage\UsageCounterInterface;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * @package    Gems
 * @subpackage Snippets
 * @since      Class available since version 1.0
 */
class UsageSnippet extends \Zalt\Snippets\TranslatableSnippetAbstract
{
    protected UsageCounterInterface $usageCounter;

    /**
     * @var array Strings describing what is edited / saved for 1 item and more than 1 item
     */
    protected array $subjects = ['item', 'items'];

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        protected readonly MenuSnippetHelper $menuSnippetHelper
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate);
    }

    public function getHtmlOutput()
    {
        $seq = $this->getHtmlSequence();
        $seq->h4(sprintf($this->_('Usage of this %s'), $this->getTopic(1)));

        $params = $this->requestInfo->getRequestMatchedParams();
        if (! $this->usageCounter->hasUsage(reset($params))) {
            $seq->pInfo($this->_('Not used!'));
        }

        $seq->ul($this->usageCounter->getUsageReport());

        return $seq;
    }

    /**
     * Helper function to allow generalized statements about the items in the model to used specific item names.
     *
     * @param int $count
     * @return string
     */
    public function getTopic($count = 1)
    {
        return $this->plural($this->subjects[0], $this->subjects[1], $count);
    }

    public function hasHtmlOutput(): bool
    {
        return isset($this->usageCounter) && $this->requestInfo->getRequestMatchedParams();
    }
}