<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets_Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets_Tracker
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class TokenStatusLegenda extends \MUtil\Snippets\SnippetAbstract
{
    /**
     *
     * @var \Gems\Util
     */
    protected $util;

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view = null)
    {
        $tUtil = $this->util->getTokenData();

        $repeater = new \MUtil\Lazy\RepeatableByKeyValue($tUtil->getEveryStatus());
        $table    = new \MUtil\Html\TableElement();
        $table->class = 'compliance timeTable rightFloat table table-condensed';
        $table->setRepeater($repeater);

        $table->throw($this->_('Legend'));
        $cell = $table->td();
        $cell->class = array(
            'round',
            \MUtil\Lazy::method($tUtil, 'getStatusClass', $repeater->key)
            );
        $cell->append(\MUtil\Lazy::method($tUtil, 'getStatusIcon', $repeater->key));
        $table->td($repeater->value);

        return $table;
    }
}
