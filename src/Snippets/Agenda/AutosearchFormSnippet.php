<?php
/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Agenda;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.5
 */
class AutosearchFormSnippet extends \Gems\Snippets\AutosearchFormSnippet
{
    /**
     * Array of model name => empty text to allow adding select boxes in a flexible way
     * 
     * When key is numeric, the value is added to the elements as-is
     * 
     * @var array
     */    
    protected $searchFields = [];
    
    public function getAutoSearchElements(array $data)
    {
        $elements = parent::getAutoSearchElements($data);
        
        foreach ($this->searchFields as $searchField => $emptyTxt) {
            if (!is_numeric($searchField)) {
                $elements[] = $this->_createSelectElement($searchField, $this->model->get($searchField, 'multiOptions'), $emptyTxt);
            } else {
                $elements[] = $emptyTxt;
            }
        }
        
        return $elements;
    }
    
}
