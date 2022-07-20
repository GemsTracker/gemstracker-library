<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Jasper van Gestel <jvangestel@gmail.com>
 * @copyright  Copyright (c) 2017 Erasmus MC
 * @license    New BSD License
 * @version
 */

/**
 * Compare two surveys and copy the answers of one to the other with adjustments
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2017 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.2
 */
class Gems_Default_UpdateSurveyAction extends \Gems_Controller_Action
{
    public function runAction()
    {
        $this->initHtml();
        $this->addSnippets('Survey\\SurveyCompareSnippet', [
            'requestInfo' => $this->getRequestInfo(),
        ]);
    }

    public function getRequestInfo(): \MUtil\Request\RequestInfo
    {
        $factory = new \MUtil\Request\RequestInfoFactory($this->request);
        return $factory->getRequestInfo();
    }
}
