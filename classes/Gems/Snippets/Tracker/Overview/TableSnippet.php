<?php
/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Display a tabel that links to the track maintenance
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.4
 */
class Gems_Snippets_Tracker_Overview_TableSnippet extends \Gems_Snippets_ModelTableSnippetGeneric
{
    /**
     * Menu actions to show in Show box.
     *
     * If controller is numeric $menuActionController is used, otherwise
     * the key specifies the controller.
     *
     * @var array (int/controller => action)
     */
    public $menuShowActions = array('track-maintenance' => 'show');
}