<?php
/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Standard controller to export respondent data to html
 *
 * @package    Gems
 * @subpackage Default
 * @author     Michiel Rook <michiel@touchdownconsulting.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */
class Gems_Default_RespondentExportAction extends \Gems_Controller_Action
{
    public $useHtmlView = true;

    public function indexAction()
    {
        $export = $this->loader->getRespondentExport();
        $form   = $export->getForm();

        $element = new \Zend_Form_Element_Textarea('id');
        $element->setLabel($this->_('Respondent numbers'))
                ->setAttrib('cols', 60)
                ->setAttrib('rows', 4)
                ->setOrder(-1)
                ->setDescription($this->_('Separate multiple respondents with a comma (,) or whitespace'));

        $form->addElement($element);

        $this->html->h2($this->_('Export respondent archive'));
        $div = $this->html->div(array('id' => 'mainform'));
        $div[] = $form;

        $request = $this->getRequest();

        $form->populate($request->getParams());

        if ($request->isPost()) {
            $respondents = preg_split('/[\s,;]+/', $request->getParam('id'), -1, PREG_SPLIT_NO_EMPTY);
            if (count($respondents)>0) {
                $export->render($respondents, $request->getParam('group'), $request->getParam('format'));
            } else {
                $this->addMessage($this->_('Please select at least one respondent'));
            }
        }
    }
}