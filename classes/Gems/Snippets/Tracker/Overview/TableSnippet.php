<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of TableSnippet
 *
 * @author 175780
 */
class Gems_Snippets_Tracker_Overview_TableSnippet extends Gems_Snippets_ModelTableSnippetGeneric {
    public function getShowMenuItem() {
        return $this->findMenuItem('track-maintenance', 'show');
    }
}