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

/**
 * Ask Yes/No conformation for deletion and deletes respondent relation when confirmed.
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class YesNoDeleteSnippet extends \Gems\Snippets\ModelItemYesNoDeleteSnippetGeneric
{
    public function render(\Zend_View_Abstract $view)
    {
        // \MUtil\EchoOut\EchoOut::r(sprintf('Rendering snippet %s.', get_class($this)));
        //
        // TODO: Change snippet workings.
        // All forms are processed twice if hasHtmlOutput() is called here. This is
        // a problem when downloading files.
        // However: not being able to call hasHtmlOutput() twice is not part of the original plan
        // so I gotta rework the forms. :(
        //
        if ((!$this->hasHtmlOutput()) && $this->getRedirectRoute()) {
            $this->redirectRoute();

        } else {
            $html = $this->getHtmlOutput($view);

            if ($html) {
                if ($html instanceof \MUtil\Html\HtmlInterface) {
                    if ($html instanceof \MUtil\Html\HtmlElement) {
                        $this->applyHtmlAttributes($html);
                    }
                    return $html->render($view);
                } else {
                    return \MUtil\Html::renderAny($view, $html);
                }
            }
        }
    }

    /**
     * Use findmenuitem for the abort action so we get the right id appended
     *
     * @param \MUtil\Model\Bridge\VerticalTableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     */
    protected function setShowTableFooter(\MUtil\Model\Bridge\VerticalTableBridge $bridge, \MUtil\Model\ModelAbstract $model)
    {
        $footer = $bridge->tfrow();

        $footer[] = $this->getQuestion();
        $footer[] = ' ';
        $footer->actionLink(array($this->confirmParameter => 1), $this->_('Yes'));
        $footer[] = ' ';
        $footer->actionLink($this->findMenuItem($this->request->getControllerName(), $this->abortAction)->toHRefAttribute($this->request), $this->_('No'));
    }
}