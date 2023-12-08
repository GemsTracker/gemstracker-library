<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Snippets\Usage
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets\Usage;

use Gems\Html;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Html\HtmlElement;
use Zalt\Html\Sequence;
use Zalt\Snippets\SnippetInterface;
use Zalt\SnippetsLoader\SnippetLoader;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * @package    Gems
 * @subpackage Snippets\Usage
 * @since      Class available since version 1.0
 */
class EnableUsageSnippet extends \Zalt\Snippets\TranslatableSnippetAbstract
{
    protected ?string $_redirectRoute = null;

    protected ?ResponseInterface $_response = null;

    protected string $currentStatus;

    protected string $disableParameter = 'hideUsage';

    protected string $enableParameter = 'showUsage';

    protected Sequence $html;

    protected HtmlElement $info;
    protected array $usageSnippets = [];

    public function __construct(
        protected readonly SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        protected readonly SessionInterface $session,
        protected readonly SnippetLoader $snippetLoader
    )
    {
        parent::__construct($this->snippetOptions, $requestInfo, $translate);
    }

    public function getHtmlOutput()
    {
        $url  = [$this->requestInfo->getBasePath()];
        if ($this->currentStatus === $this->enableParameter) {
            $url[$this->disableParameter] = '1';
            $this->info->a($url, $this->_('Hide usage'));
        } else {
            $url[$this->enableParameter] = '1';
            $this->info->a($url, $this->_('Show usage'));
        }

        return $this->html;
    }

    public function getRedirectRoute(): ?string
    {
        return $this->_redirectRoute;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->_response;
    }

    public function hasHtmlOutput(): bool
    {
        $sessionId = $this->requestInfo->getRouteName() . '.usage';
        $oldStatus = $this->session->get($sessionId, $this->disableParameter);
        $params    = $this->requestInfo->getParams();
        // dump($this->requestInfo);

        if (array_key_exists($this->disableParameter, $params)) {
            $this->currentStatus = $this->disableParameter;
        } elseif (array_key_exists($this->enableParameter, $params)) {
            $this->currentStatus = $this->enableParameter;
        } else {
            $this->currentStatus = $oldStatus;
        }

        if ($this->currentStatus !== $oldStatus) {
            $this->session->set($sessionId, $this->currentStatus);
        }
        $this->html = $this->getHtmlSequence();
        $this->info = Html::create('pInfo');
        $this->html->append($this->info);

        if ($this->currentStatus === $this->enableParameter) {
            foreach ($this->usageSnippets as $filename) {
                $snippet = $this->snippetLoader->getSnippet($filename, $this->snippetOptions);
                if ($snippet instanceof SnippetInterface) {
                    if ($snippet->hasHtmlOutput()) {
                        $this->html->append($snippet);

                    } else {
                        $this->_redirectRoute = $snippet->getRedirectRoute();
                        if ($this->_redirectRoute) {
                            return false;
                        }
                        $this->_response = $snippet->getResponse();
                        if ($this->_response) {
                            return false;
                        }
                    }
                }
            }
            // dump($this->usageSnippets);
        }
        return true;
    }
}