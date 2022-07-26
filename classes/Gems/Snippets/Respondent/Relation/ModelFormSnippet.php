<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Respondent\Relation;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class ModelFormSnippet extends \Gems\Snippets\ModelFormSnippetGeneric {

    protected function setAfterSaveRoute() {
        $this->afterSaveRouteUrl = array(
            'action'                 => 'index',
            'controller'             => 'respondent-relation',
            \MUtil\Model::REQUEST_ID1 => $this->request->getParam(\MUtil\Model::REQUEST_ID1),
            \MUtil\Model::REQUEST_ID2 => $this->request->getParam(\MUtil\Model::REQUEST_ID2),
        );

        $this->resetRoute = true;

        //parent::setAfterSaveRoute();

        return $this;
    }

}