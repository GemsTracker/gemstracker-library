<?php

/**
 * Copyright (c) 2016, J-POP Foundation
 * All rights reserved.
 *
 * @package    Booth
 * @subpackage Snippets\Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016 J-POP Foundation
 * @license    no free license, do not use without permission
 */

namespace Gems\Snippets\Agenda;

/**
 *
 *
 * @package    Booth
 * @subpackage Snippets\Agenda
 * @copyright  Copyright (c) 2016 J-POP Foundation
 * @license    no free license, do not use without permission
 * @since      Class available since Jun 16, 2016 5:11:31 PM
 */
class YesNoAppointmentDeleteSnippet extends \Gems_Snippets_ModelItemYesNoDeleteSnippetGeneric
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
     * @return \MUtil_Snippets_ModelYesNoDeleteSnippetAbstract
     */
    protected function setAfterDeleteRoute()
    {
        parent::setAfterDeleteRoute();

        if ($this->afterSaveRouteUrl) {
            $this->afterSaveRouteUrl[\Gems_Model::APPOINTMENT_ID] = $this->request->getParam(\Gems_Model::APPOINTMENT_ID);
        }
    }
}
