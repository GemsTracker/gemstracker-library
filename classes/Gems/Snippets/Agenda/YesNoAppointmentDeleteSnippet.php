<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Agenda;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */

class YesNoAppointmentDeleteSnippet extends \Gems\Snippets\ModelItemYesNoDeleteSnippetGeneric
{
    /**
     * The action to go to when the user clicks 'Yes' and the data is deleted.
     *
     * If you want to change to another controller you'll have to code it.
     *
     * @var string
     */
    protected $deleteAction = 'show';

    /**
     * Set what to do when the form is 'finished'.
     *
     * @return \MUtil\Snippets\ModelYesNoDeleteSnippetAbstract
     */
    protected function setAfterDeleteRoute()
    {
        parent::setAfterDeleteRoute();

        if ($this->afterSaveRouteUrl) {
            $this->afterSaveRouteUrl[\Gems\Model::APPOINTMENT_ID] = $this->request->getParam(\Gems\Model::APPOINTMENT_ID);
        }
    }
}
