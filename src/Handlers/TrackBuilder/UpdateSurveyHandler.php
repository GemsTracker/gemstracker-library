<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Jasper van Gestel <jvangestel@gmail.com>
 * @copyright  Copyright (c) 2017 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\TrackBuilder;

use MUtil\Handler\SnippetLegacyHandlerAbstract;

/**
 * Compare two surveys and copy the answers of one to the other with adjustments
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2017 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.2
 */
class UpdateSurveyHandler extends SnippetLegacyHandlerAbstract
{
    public function runAction()
    {
        $this->addSnippets('Survey\\SurveyCompareSnippet');
    }
}
