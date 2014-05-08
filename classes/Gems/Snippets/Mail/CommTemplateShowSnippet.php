<?php

/**
 * Copyright (c) 2013, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $Id: CommTemplateShowSnippet.php 1733 2014-02-13 17:58:34Z matijsdejong $
 */

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class Gems_Snippets_Mail_CommTemplateShowSnippet extends Gems_Snippets_ModelItemTableSnippetGeneric
{
    protected $subTitleItem = 'gctt_lang';

    protected $submodel;

    /**
     * Adds rows from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addShowTableRows(MUtil_Model_Bridge_VerticalTableBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        $items = $model->getItemsOrdered();
        foreach($items as $name) {
            if ($model->get($name, 'type') === MUtil_Model::TYPE_CHILD_MODEL) {
                $this->submodel = $model->get($name, 'model');
                $subitems = $this->submodel->getItemsOrdered();
            }
        }

        if (isset($subitems) && is_array($subitems)) {
            $items = array_diff($items, $subitems);
        }

        foreach($items as $name) {
            if ($label = $model->get($name, 'label')) {
                $bridge->addItem($name, $label);
            }
        }

        /*if ($subitems) {
            $bridge->addItem('gctt', 'moo');
        }*/

        if ($model->has('row_class')) {
            // Make sure deactivated rounds are show as deleted
            foreach ($bridge->getTable()->tbody() as $tr) {
                foreach ($tr as $td) {
                    if ('td' === $td->tagName) {
                        $td->appendAttrib('class', $bridge->row_class);
                    }
                }
            }
        }
    }

    protected function addSubModelTable($subContainer)
    {
    	if ($this->submodel) {
    		$data = $this->loadData();
    		$submodelName = $this->submodel->getName();
            $multi = false;
            if (count($data[$submodelName]) > 1) {
                $multi = true;
            }
    		foreach($data[$submodelName] as $item) {
        		if ($multi && isset($this->subTitleItem) && isset($item[$this->subTitleItem])) {
        			$subContainer->h3($item[$this->subTitleItem]);
        		}
        		$subTable = $subContainer->table();
           		foreach($item as $subColumnName => $subValue) {
           			if ($subLabel = $this->submodel->get($subColumnName, 'label')) {
        				$row = $subTable->tr();
        				$row->th()->append($subLabel);
        				$row->td()->append($this->processValue($subColumnName, $subValue, $this->submodel));
        			}
        		}
        	}

    	}
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param Zend_View_Abstract $view Just in case it is needed here
     * @return MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(Zend_View_Abstract $view)
    {
        $model = $this->getModel();
        if ($this->trackUsage) {
            $model->trackUsage();
        }

        $table = $this->getShowTable($model);
        if ($this->submodel) {
            $subrow = $table->tr();
            $subrow->th();
            $subContainer = $subrow->td();
            $this->addSubModelTable($subContainer);
        }
        $table->setRepeater($this->getRepeater($model));


        return $table;
    }

    protected function loadData()
	{
		return $this->model->loadFirst();
	}

	protected function processValue($name, $value, $model=false)
	{
		if (!$model) {
			$model = $this->model;
		}
		$result = $value;

		if ($default = $model->get($name, 'default')) {
            if (null === $result) {
                $result = $default;
            }
        }
        if ($formatFunction = $model->get($name, 'formatFunction')) {

            $result = call_user_func($formatFunction, $result);
        } elseif ($dateFormat = $model->get($name, 'dateFormat')) {

            $storageFormat = $model->get($name, 'storageFormat');

            $result = MUtil_Date::format($result, $dateFormat, $storageFormat);
        }

        if ($itemDisplay = $model->get($name, 'itemDisplay')) {

            if (is_callable($itemDisplay)) {
                $result = call_user_func($itemDisplay, $result);

            } elseif (is_object($itemDisplay)) {

                if (($itemDisplay instanceof MUtil_Html_ElementInterface)
                    || method_exists($itemDisplay, 'append')) {

                    $object = clone $itemDisplay;

                    $result = $object->append($result);
                }

            } elseif (is_string($itemDisplay)) {
                // Assume it is a html tag when a string

                $result = MUtil_Html::create($itemDisplay, $result);
            }
        }
        return $result;
	}
}