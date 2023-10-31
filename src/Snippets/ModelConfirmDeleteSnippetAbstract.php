<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets;

use Gems\Menu\MenuSnippetHelper;
use Gems\Usage\UsageCounterInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Message\MessengerInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Type\ActivatingYesNoType;
use Zalt\Snippets\DeleteModeEnum;
use Zalt\Snippets\ModelBridge\DetailTableBridge;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * @package    Gems
 * @subpackage Snippets
 * @since      Class available since version 1.0
 */
abstract class ModelConfirmDeleteSnippetAbstract extends \Zalt\Snippets\ModelConfirmDeleteSnippetAbstract
{
    use TopicCallableTrait;

    protected string $abortRoute = 'show';

    public string $buttonBlockedClass = 'actionlink btn disabled';

    protected $class = 'displayer table';

    protected string $deleteRoute = 'index';

    protected string $formClass = 'form-row';

    protected UsageCounterInterface $usageCounter;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        protected readonly MenuSnippetHelper $menuSnippetHelper)
    {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger);

        $abortRoute = $this->menuSnippetHelper->getRelatedRoute($this->abortRoute);
        if ($abortRoute) {
            $this->abortUrl = $this->menuSnippetHelper->getRouteUrl($abortRoute, $requestInfo->getRequestMatchedParams());
        }
    }

    public function getActivationMessage(): string
    {
        return sprintf(
            $this->_('One %s activated!'),
            $this->getTopic(1)
        );
    }

    public function getActivationQuestion(): string
    {
        return sprintf(
            $this->_('Do you want to activate this %s?'),
            $this->getTopic(1)
        );
    }

    public function getBlockedQuestion(): string
    {
        return sprintf(
            $this->_('Deletion of this %s is blocked!'),
            $this->getTopic(1)
        );
    }


    public function getDeactivationMessage(): string
    {
        return sprintf(
            $this->_('One %s deactivated!'),
            $this->getTopic(1)
        );
    }

    public function getDeactivationQuestion(): string
    {
        return sprintf(
            $this->_('Do you want to deactivate this %s?'),
            $this->getTopic(1)
        );
    }

    public function getDeletionMessage(): string
    {
        return sprintf(
            $this->_('One %s deleted!'),
            $this->getTopic(1)
        );
    }

    protected function getDeletionMode(DataReaderInterface $dataModel): DeleteModeEnum
    {
        $output = parent::getDeletionMode($dataModel);

        if (isset($this->usageCounter) && (! ActivatingYesNoType::hasActivation($dataModel->getMetaModel()))) {
            // Load dependencies
            $row = $dataModel->loadFirst();
            $output = $this->usageCounter->getUsageMode();
        }

        // Set correct route
        if ($output != DeleteModeEnum::Delete) {
            $this->deleteRoute = 'show';
        }
        $route = $this->menuSnippetHelper->getRelatedRoute($this->deleteRoute);
        if ($route) {
            $this->afterActionUrl = $this->menuSnippetHelper->getRouteUrl($route, $this->requestInfo->getRequestMatchedParams());
        }

        return $output;
    }

    public function getDeletionQuestion(): string
    {
        return sprintf(
            $this->_('Do you want to delete this %s?'),
            $this->getTopic(1)
        );
    }

    protected function getMessage(): string
    {
        if ($this->afterActionMessage) {
            return $this->afterActionMessage;
        }

        return match($this->deletionMode) {
            DeleteModeEnum::Delete => $this->getDeletionMessage(),
            DeleteModeEnum::Deactivate => $this->getDeactivationMessage(),
            DeleteModeEnum::Activate => $this->getActivationMessage(),
            DeleteModeEnum::Block => '',
        };
    }

    protected function getQuestion(): string
    {
        if (isset($this->question)) {
            return $this->question;
        }

        return match($this->deletionMode) {
            DeleteModeEnum::Delete => $this->getDeletionQuestion(),
            DeleteModeEnum::Deactivate => $this->getDeactivationQuestion(),
            DeleteModeEnum::Activate => $this->getActivationQuestion(),
            DeleteModeEnum::Block => $this->getBlockedQuestion(),
        };
    }

    public function getYesButtonLabel(): string
    {
        return match ($this->deletionMode) {
            DeleteModeEnum::Delete => $this->_('Delete!!'),
            DeleteModeEnum::Deactivate => $this->_('Deactivate'),
            DeleteModeEnum::Activate => $this->_('Reactivate'),
            DeleteModeEnum::Block => $this->_('Blocked!'),
        };
    }
}