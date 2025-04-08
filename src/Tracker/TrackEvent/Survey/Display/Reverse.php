<?php

/**
 *
 * @package    Gems
 * @subpackage Events
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\TrackEvent\Survey\Display;

use Gems\Tracker\TrackEvent\SurveyAnswerFilterAbstract;
use Zalt\Base\RequestInfo;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelBridge\TableBridge;

/**
 * Put the last question first
 *
 * @package    Gems
 * @subpackage Events
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.6
 */
class Reverse extends SurveyAnswerFilterAbstract
{
    /**
     * This function is called in addBrowseTableColumns() to filter the names displayed
     * by AnswerModelSnippetGeneric.
     *
     * @param TableBridge $bridge
     * @param DataReaderInterface $model
     * @param array $currentNames The current names in use (allows chaining)
     * @param RequestInfo $requestInfo
     * @return array Of the names of labels that should be shown
     *@see AnswerModelSnippetGeneric
     *
     */
    public function filterAnswers(TableBridge $bridge, DataReaderInterface $model, array $currentNames, RequestInfo $requestInfo): array
    {
        return $this->restoreHeaderPositions($model, array_reverse($currentNames));
    }

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName(): string
    {
        return $this->translator->_('Reverse the question order.');
    }
}
