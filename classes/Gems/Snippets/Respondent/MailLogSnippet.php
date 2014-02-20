<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of MailLogSnippet
 *
 * @author 175780
 */
class Gems_Snippets_Respondent_MailLogSnippet extends Gems_Snippets_Mail_Log_MailLogBrowseSnippet {
    protected function addBrowseTableColumns(MUtil_Model_TableBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        // Newline placeholder
        $br = MUtil_Html::create('br');

        // make sure search results are highlighted
        $this->applyTextMarker();

        $bridge->tr(array('onlyWhenChanged' => true, 'class' => 'even'));
        $bridge->addSortable('gtr_track_name')->colspan = 4;
        
        /*$bridge->addMultiSort('grco_created',  $br, 'respondent_name', $br, 'grco_address', $br, 'gtr_track_name');
        $bridge->addMultiSort('grco_id_token', $br, 'assigned_by',     $br, 'grco_sender',  $br, 'gsu_survey_name');
        $bridge->addMultiSort('status',        $br, 'grco_topic');
         * 
         */
        $bridge->tr(array('class' => 'odd'));
        
        $bridge->addMultiSort('grco_created',  $br, 'ggp_name', $br, 'grco_address');
        $bridge->addMultiSort('grco_id_token', $br, 'assigned_by',     $br, 'grco_sender');
        $bridge->addMultiSort('status',        $br, 'grco_topic',      $br, 'gsu_survey_name');

        $title = MUtil_Html::create()->strong($this->_('+'));
        $params = array(
            'gto_id_token'  => $bridge->gto_id_token,
            'gtr_track_type' => $bridge->gtr_track_type,
            'grc_success' => 1,
            Gems_Model::ID_TYPE => 'token',
            );
        
        $showLinks = array();

        $showLinks[]   = $this->createMenuLink($params, 'track',  'show', $title);
        $showLinks[]   = $this->createMenuLink($params, 'survey', 'show', $title);

        // Remove nulls
        $showLinks   = array_filter($showLinks);

        if ($showLinks) {
            foreach ($showLinks as $showLink) {
                if ($showLink) {
                    $showLink->title = array($this->_('Token'), $bridge->gto_id_token->strtoupper());
                }
            }
        }
        
        $bridge->addItemLink($showLinks);
    }
}