<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Respondent\Relation;

use Gems\Html;
use Zalt\Html\HtmlElement;
use Zalt\Html\HtmlInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelBridge\DetailTableBridge;

/**
 * Ask Yes/No conformation for deletion and deletes respondent relation when confirmed.
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class YesNoDeleteSnippet extends \Gems\Snippets\ModelItemYesNoDeleteSnippet
{
    /**
     * Use findmenuitem for the abort action so we get the right id appended
     *
     * @param DetailTableBridge $bridge
     * @param DataReaderInterface $dataModel
     * @return void
     */
    protected function setShowTableFooter(DetailTableBridge $bridge, DataReaderInterface $dataModel)
    {
        $footer = $bridge->tfrow();

        $footer[] = $this->getQuestion();
        $footer[] = ' ';
        $footer->actionLink(array($this->confirmParameter => 1), $this->_('Yes'));
        $footer[] = ' ';
        $footer->actionLink($this->findMenuItem($this->request->getControllerName(), $this->abortAction)->toHRefAttribute($this->request), $this->_('No'));
    }
}