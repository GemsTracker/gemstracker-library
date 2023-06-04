<?php

/**
 *
 * @package    Gems
 * @subpackage Screens\Respondent\Edit
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Screens\Respondent\Edit;

use Gems\Screens\EditScreenAbstract;

/**
 *
 * @package    Gems
 * @subpackage Screens\Respondent\Edit
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 Jan 20, 2017 3:52:09 PM
 */
class GemsDefaultNoTabEdit extends EditScreenAbstract
{
    /**
     *
     * @return array Added before all other parameters
     */
    public function getCreateParameters()
    {
        return ['respondent' => null] + $this->getParameters();
    }

    /**
     *
     * @return array Default added parameters
     */
    protected function getParameters()
    {
        return [
            'menuShowSiblings' => true,
            'menuShowChildren' => true,
            'resetRoute'       => true,
            'useTabbedForm'    => false,
            ];
    }

    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil\Html\HtmlElement
     */
    public function getScreenLabel()
    {
        return $this->_('(default \Gems respondent no tab edit)');
    }

    /**
     *
     * @return array Of snippets or false to use original
     */
    public function getSnippets()
    {
        return [
            'Gems\\Snippets\\Respondent\\RespondentFormSnippet',
            'Gems\\Snippets\\Respondent\\Consent\\RespondentConsentLogSnippet',
        ];
    }
}
