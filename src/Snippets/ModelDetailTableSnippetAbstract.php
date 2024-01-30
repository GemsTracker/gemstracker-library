<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets;

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

    protected $class = 'displayer table';

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
}