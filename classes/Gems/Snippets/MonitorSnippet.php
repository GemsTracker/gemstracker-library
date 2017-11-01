<?php

/**
 * @package    Gems
 * @subpackage Snippets
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2017 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets;

use MUtil\Util\MonitorJob;

/**
 * Snippet to display information about a specific monitor job
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2017 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.3
 */
class MonitorSnippet extends \MUtil_Snippets_SnippetAbstract
{
    public $caption;
    /**
     *
     * @var MonitorJob
     */
    public $monitorJob;
    
    public $title;
    
    public function afterRegistry()
    {
        parent::afterRegistry();
        
        if (is_null($this->caption)) {
            $this->caption = $this->_(sprintf('Monitorjob %s', $this->monitorJob->getName()));
        }
        
        if (is_null($this->title)) {
            $this->title = $this->_('Monitorjob overview');
        }
    }

    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $seq = $this->getHtmlSequence();
        
        $data = $this->getReadableData();
        $tableContainer = \MUtil_Html::create()->div(array('class' => 'table-container'));
        $table = \MUtil_Html_TableElement::createArray($data, $this->caption);
        $table->class = 'browser table';
        $tableContainer[] = $table;
        
        $seq->h3($this->title);
        $seq[] = $tableContainer;
                
        return $seq;
    }
    
    /**
     * Create readable output
     * 
     * @return array
     */
    protected function getReadableData()
    {
        $job  = $this->monitorJob;
        $data = $job->getArrayCopy();
        
        $data['firstCheck'] = date(MonitorJob::$monitorDateFormat, $data['firstCheck']);
        $data['checkTime'] = date(MonitorJob::$monitorDateFormat, $data['checkTime']);
        $data['setTime'] = date(MonitorJob::$monitorDateFormat, $data['setTime']);
        $period = $data['period'];
        $mins = $period % 3600;
        $secs = $mins % 60;
        $hours = ($period - $mins) / 3600;
        $mins = ($mins - $secs) / 60;
        $data['period'] = sprintf('%2d:%02d:%02d',$hours,$mins,$secs);
        
        return $data;
    }


}