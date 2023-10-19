<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets;

use Gems\Menu\MenuSnippetHelper;
use Zalt\Base\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Message\MessengerInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * @package    Gems
 * @subpackage Snippets
 * @since      Class available since version 1.0
 */
abstract class ModelConfirmSnippetAbstract extends \Zalt\Snippets\ModelConfirmSnippetAbstract
{
    use TopicCallableTrait;

    protected string $abortRoute = 'show';

    protected $class = 'displayer table';

    protected string $deleteRoute = 'index';

    protected string $formClass = 'form-row';

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

    public function checkModel(FullDataInterface $dataModel): void
    {
        parent::checkModel($dataModel);

        if (self::MODE_DELETE == $this->actionMode) {
            $afterActionRoute = $this->menuSnippetHelper->getRelatedRoute($this->deleteRoute);
            if ($afterActionRoute) {
                $this->afterActionUrl = $this->menuSnippetHelper->getRouteUrl($afterActionRoute, $this->requestInfo->getRequestMatchedParams());
            }
        }
        if (!$this->afterActionUrl) {
            $this->afterActionUrl = $this->abortUrl;
        }
    }

    public function getYesButtonLabel(): string
    {
        return match ($this->actionMode) {
            self::MODE_DELETE => $this->_('Delete!!'),
            self::MODE_DEACTIVATE => $this->_('Deactivate'),
            self::MODE_ACTIVATE => $this->_('Reactivate'),
            default => parent::getYesButtonLabel(),
        };
    }
}