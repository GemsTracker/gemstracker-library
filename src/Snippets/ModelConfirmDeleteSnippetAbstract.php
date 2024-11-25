<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets;

use Gems\Audit\AuditLog;
use Gems\Menu\MenuSnippetHelper;
use Gems\Usage\UsageCounterInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Html\Html;
use Zalt\Html\HtmlElement;
use Zalt\Message\MessengerInterface;
use Zalt\Model\Bridge\BridgeInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Snippets\DeleteModeEnum;
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

    /**
     * The title to display
     *
     * @var string
     */
    protected string $contentTitle;

    /**
     * Tagname of the HtmlElement to create
     *
     * @var string
     */
    protected string $tagName = 'h2';

    protected string $deleteRoute = 'index';

    protected string $formClass = 'form-row';

    protected UsageCounterInterface $usageCounter;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        protected readonly AuditLog $auditLog,
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
            $this->_('One %s reactivated!'),
            $this->getTopic(1)
        );
    }

    public function getActivationQuestion(): string
    {
        return sprintf(
            $this->_('Do you want to reactivate this %s?'),
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

        if (isset($this->usageCounter)) {
            // Load dependencies
            $row = $dataModel->loadFirst();
            $output = $this->usageCounter->getUsageMode();
        }

        // Set correct route
        if ($output !== DeleteModeEnum::Delete) {
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

    public function getHtmlOutput()
    {
        if (! $this->onEmpty) {
            $this->onEmpty = $this->getOnEmpty();
        }

        $html = $this->getHtmlSequence();

        $html->append($this->getTitleElement());
        $html->append(parent::getHtmlOutput());

        return $html;
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

    public function getOnEmpty(): mixed
    {
        if ($this->requestInfo->getParam(MetaModelInterface::REQUEST_ID)) {
            return sprintf(
                $this->_('%s "%s" not found!'),
                ucfirst($this->getTopic(1)),
                $this->requestInfo->getParam(MetaModelInterface::REQUEST_ID)
            );
        }
        return sprintf(
            $this->_('%s not found!'),
            ucfirst($this->getTopic(1))
        );
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

    protected function getTitleElement(): HtmlElement
    {
        return Html::create($this->tagName, $this->getTitleString(), array('class' => 'title'));
    }

    protected function getTitleString(): string
    {
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

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function hasHtmlOutput(): bool
    {
        if ($this->bridgeMode === BridgeInterface::MODE_SINGLE_ROW) {
            $bridge = $this->getModel()->getBridgeFor($this->bridgeClass);
            $row = $bridge->getRow();
            if (empty($row)) {
                $this->setNotFound();
                return false;
            }
        }

        return parent::hasHtmlOutput();
    }

    public function isActionConfirmed() : bool
    {
        if (parent::isActionConfirmed()) {
            $this->logChanges();

            return true;
        }

        return false;
    }

    protected function logChanges(): void
    {
        switch ($this->deletionMode) {
            case DeleteModeEnum::Activate:
            case DeleteModeEnum::Deactivate:
                $this->auditLog->registerChanges($this->actionValues, messages: $this->messenger->getMessages(keep: true));
                return;

            case DeleteModeEnum::Delete:
                $this->auditLog->registerChanges(['row' => 'deleted'], messages: $this->messenger->getMessages(keep: true));
                return;

            case DeleteModeEnum::Block:
                // Do nothing
        }
    }

    protected function setNotFound(): void
    {
        $this->response = new HtmlResponse(sprintf(
            $this->_('%s not found!'),
            ucfirst($this->getTopic(1))), 404);
    }
}