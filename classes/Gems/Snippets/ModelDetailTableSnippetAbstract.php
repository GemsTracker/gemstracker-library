<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets;

use MUtil\Model;
use Zalt\Html\HtmlException;

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
        file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  print_r($this->requestInfo->getParams(), true) . "\n", FILE_APPEND);

        if ($this->requestInfo->getParam(Model::REQUEST_ID)) {
            return sprintf(
                $this->_('%s "%s" not found!'),
                ucfirst($this->getTopic(1)),
                $this->requestInfo->getParam(Model::REQUEST_ID)
            );
        }
        return sprintf(
            $this->_('%s not found!'),
            ucfirst($this->getTopic(1))
        );
    }
}