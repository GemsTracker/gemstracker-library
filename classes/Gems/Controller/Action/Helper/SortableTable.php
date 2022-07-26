<?php

/**
 *
 * @package    Gems
 * @subpackage Controller\Action
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Controller\Action\Helper;

/**
 * This action helper makes sorting table data that has an order field easier
 *
 * Implement by adding the buttons returned by the direct method to the bottom
 * of the table output. Create an action that calls the ajaxRequest method.
 *
 * @package    Gems
 * @subpackage Controller\Action
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.1
 */
class SortableTable extends \Zend_Controller_Action_Helper_Abstract
{
    /**
     * Handles sort in an ajax request
     *
     * @param string $table The table used
     * @param string $idField The name of the field with the primary key
     * @param string $orderField The name of the field that holds the order
     * @return void
     * @throws \Gems\Exception
     */
    public function ajaxAction($table, $idField, $orderField)
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $ids = $request->getPost('ids');
            foreach($ids as $id) {
                $cleanIds[] = (int) $id;
            }
            $db = $this->getActionController()->db;
            $select = $db->select()->from($table, array($idField, $orderField))
                    ->where($idField . ' in(?)', $cleanIds)
                    ->order($orderField);

            $oldOrder = $db->fetchPairs($select);

            if(count($oldOrder) == count($cleanIds)) {
                $newOrder = array_combine($cleanIds, $oldOrder);

                $changed = 0;
                foreach($newOrder as $id => $order)
                {
                    $changed = $changed + $db->update($table,
                            array($orderField => $order),
                            $db->quoteInto($idField . ' = ?', $id)
                            );
                }

                $this->getActionController()->addMessage(sprintf($this->getActionController()->plural('%s record updated due to sorting.', '%s records updated due to sorting.', $changed), $changed));

                return;
            }
        }

        throw new \Gems\Exception($this->_('Sorting failed'), 403);
    }

    /**
     * Get the sort buttons to add under the table with sortable rows
     *
     * @param string $sortAction The name of the ajax action
     * @param string $urlIdParam The namr used to refer to the record ID in the url
     * @return \MUtil\Html\HtmlElement
     */
    public function direct($sortAction = 'sort', $urlIdParam = 'id')
    {
        $view = $this->getView();
        \MUtil\JQuery::enableView($view);

        $jquery = $view->jQuery();
        $jquery->enable();  //Just to make sure

        $handler = \ZendX_JQuery_View_Helper_JQuery::getJQueryHandler();
        $url     = $view->serverUrl() . $view->baseUrl() . '/' . $this->getRequest()->getControllerName() . '/' . $sortAction;

        $script = file_get_contents(__DIR__ . '/js/SortableTable.js');
        $fields = array(
            'jQuery'  => $handler,
            'AJAXURL' => $url,
            'IDPARAM' => $urlIdParam
        );

        $js = str_replace(array_keys($fields), $fields, $script);

        $jquery->addOnLoad($js);

        $buttons        = \Mutil_Html::div();
        $buttons->class = 'buttons pull-right';

        $buttons->div($this->getActionController()->_('Sort'), array('id' => 'sort', 'class' => "btn"));
        $buttons->div($this->getActionController()->_('Ok'), array('id' => 'sort-ok', 'class' => "btn btn-success", 'style' => 'display:none;'));
        $buttons->div($this->getActionController()->_('Cancel'), array('id' => 'sort-cancel', 'class' => "btn btn-warning", 'style' => 'display:none;'));

        return $buttons;
    }

    /**
     * Get the view
     *
     * @return \Zend_View_Interface
     */
    public function getView()
    {
        $controller = $this->getActionController();
        if (null === $controller) {
            $controller = $this->getFrontController();
        }

        return $controller->view;
    }

}
