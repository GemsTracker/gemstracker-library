<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets;

use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Zalt\Model\Bridge\BridgeInterface;
use Zalt\Model\MetaModelInterface;

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @since      Class available since version 1.9.2
 */
abstract class ModelDetailTableSnippetAbstract extends \Zalt\Snippets\ModelDetailTableSnippetAbstract
{
    use TopicCallableTrait;

    protected $bridgeMode = BridgeInterface::MODE_SINGLE_ROW;
    
    protected $class = 'displayer table';

    protected ResponseInterface|null $response;
    
    public function getHtmlOutput()
    {
        if (! $this->onEmpty) {
            $this->onEmpty = $this->getOnEmpty();
        }

        return parent::getHtmlOutput();
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

    protected function setNotFound(): void
    {
        $this->response = new HtmlResponse(sprintf(
            $this->_('%s not found!'),
            ucfirst($this->getTopic(1))), 404);
    }
}