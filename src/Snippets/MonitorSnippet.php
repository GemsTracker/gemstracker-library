<?php

/**
 * @package    Gems
 * @subpackage Snippets
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2017 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets;

use Gems\Html;
use Gems\Menu\MenuSnippetHelper;
use Gems\Util\Monitor\MonitorJob;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\TableElement;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * Snippet to display information about a specific monitor job
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2017 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.3
 */
class MonitorSnippet extends \Zalt\Snippets\TranslatableSnippetAbstract
{
    public ?string $caption = null;

    public $confirmParameter = 'delete';

    /**
     *
     * @var MonitorJob
     */
    public MonitorJob $monitorJob;
    
    public ?string $title = null;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        protected readonly MenuSnippetHelper $menuSnippetHelper,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate);

        if (null === $this->title) {
            $this->title = $this->_('Monitorjob overview');
        }
    }

    public function getHtmlOutput()
    {
        $seq = $this->getHtmlSequence();
        $seq->h3($this->title);

        if (! $this->title) {
            $this->caption = $this->_(sprintf('Monitorjob %s', $this->monitorJob->getName()));
        }

        if ($this->requestInfo->getParam($this->confirmParameter)) {
            $this->monitorJob->stop();
            // Now clear the job so it is empty
            $this->monitorJob = MonitorJob::getJob($this->monitorJob->getName());
        }

        $data = $this->getReadableData();
        if (empty($data)) {
            $seq[] = sprintf($this->_('No monitorjob found for %s'), $this->monitorJob->getName());
        } else {
            $tableContainer   = Html::create()->div(array('class' => 'table-container'));
            $table            = TableElement::createArray($data, $this->caption);

            $table->class     = 'browser table';
            $tableContainer[] = $table;
            $seq[]            = $tableContainer;

            $seq->actionLink([$this->menuSnippetHelper->getCurrentUrl(), $this->confirmParameter => 1], $this->_('Delete'));
        }

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
        
        // Skip when job is not started
        if ((! isset($data['setTime'])) || $data['setTime'] == 0) {
            return;
        }
        
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

    public function hasHtmlOutput(): bool
    {
        return isset($this->monitorJob);
    }
}