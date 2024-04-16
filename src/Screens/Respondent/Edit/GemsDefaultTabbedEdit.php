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
class GemsDefaultTabbedEdit extends EditScreenAbstract
{
    /**
     *
     * @return array Added before all other parameters
     */
    public function getCreateParameters(): array
    {
        return ['respondent' => null] + $this->getParameters();
    }

    /**
     *
     * @return array Default added parameters
     */
    protected function getParameters(): array
    {
        return [
            'menuShowSiblings' => true,
            'menuShowChildren' => true,
            'resetRoute'       => true,
            'useTabbedForm'    => true,
            ];
    }

    /**
     *
     * @inheritDoc
     */
    public function getScreenLabel(): string
    {
        return $this->translator->_('(default \Gems respondent tabbed edit)');
    }

    /**
     *
     * @return array Of snippets or false to use original
     */
    public function getSnippets(): array
    {
        return [
            'Gems\\Snippets\\Respondent\\RespondentFormSnippet',
            'Gems\\Snippets\\Respondent\\Consent\\RespondentConsentLogSnippet',
        ];
    }
}
