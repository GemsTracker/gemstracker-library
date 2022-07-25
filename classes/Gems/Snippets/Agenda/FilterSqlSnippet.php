<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Agenda;

use Gems\Agenda\AppointmentFilterInterface;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 18-Dec-2019 12:01:58
 */
class FilterSqlSnippet extends \MUtil\Snippets\SnippetAbstract
{
    /**
     *
     * @var \Gems\Agenda\AppointmentFilterInterface
     */
    protected $calSearchFilter;

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $html = $this->getHtmlSequence();
        $html->h4($this->_('Episode SQL'));
        $html->pre($this->calSearchFilter->getSqlEpisodeWhere());
        $html->h4($this->_('Appointment SQL'));
        $html->pre($this->calSearchFilter->getSqlAppointmentsWhere());

        return $html;
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil\Registry\TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        return $this->calSearchFilter instanceof AppointmentFilterInterface;
    }
}
