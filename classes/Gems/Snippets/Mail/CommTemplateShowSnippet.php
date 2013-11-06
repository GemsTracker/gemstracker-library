<?php

class Gems_Snippets_Mail_CommTemplateShowSnippet extends Gems_Snippets_ModelItemTableSnippetGeneric
{
    /**
     * Adds rows from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_TableBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @return void
     */
    
    protected $subTitleItem = 'gctt_lang';

    protected $submodel;

    protected function addShowTableRows(MUtil_Model_VerticalTableBridge $bridge, MUtil_Model_ModelAbstract $model)
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