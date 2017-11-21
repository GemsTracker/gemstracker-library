<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets_Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets_Tracker
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Snippets_Tracker_TokenStatusLegenda extends \MUtil_Snippets_SnippetAbstract
{
    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $tUtil = $this->util->getTokenData();

        $repeater = new \MUtil_Lazy_RepeatableByKeyValue($tUtil->getEveryStatus());
        $table    = new \MUtil_Html_TableElement();
        $table->class = 'compliance timeTable rightFloat table table-condensed';
        $table->setRepeater($repeater);

        $table->throw($this->_('Legend'));
        $cell = $table->td();
        $cell->class = array(
            'round',
            \MUtil_Lazy::method($tUtil, 'getStatusClass', $repeater->key)
            );
        $cell->append(\MUtil_Lazy::method($tUtil, 'getStatusIcon', $repeater->key));
        $table->td($repeater->value);

        return $table;
    }
}
