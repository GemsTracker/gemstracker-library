<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: ModelFormSnippet.php 956 2012-09-25 15:34:45Z matijsdejong $
 */

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class Gems_Snippets_Respondent_Relation_ModelFormSnippet extends \Gems_Snippets_ModelFormSnippetGeneric {

    protected function setAfterSaveRoute() {
        $this->afterSaveRouteUrl = array(
            'action'                 => 'index',
            'controller'             => 'respondent-relation',
            \MUtil_Model::REQUEST_ID1 => $this->request->getParam(\MUtil_Model::REQUEST_ID1),
            \MUtil_Model::REQUEST_ID2 => $this->request->getParam(\MUtil_Model::REQUEST_ID2),
        );

        $this->resetRoute = true;

        //parent::setAfterSaveRoute();

        return $this;
    }

}