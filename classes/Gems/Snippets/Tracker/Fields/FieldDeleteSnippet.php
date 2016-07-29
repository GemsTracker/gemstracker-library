<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

namespace Gems\Snippets\Tracker\Fields;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class FieldDeleteSnippet extends \Gems_Snippets_ModelItemYesNoDeleteSnippetGeneric
{
    /**
     * Set what to do when the form is 'finished'.
     *
     * @return \Gems_Snippets_Tracker_Fields_FieldDeleteSnippet (continuation pattern)
     */
    protected function setAfterDeleteRoute()
    {
        parent::setAfterDeleteRoute();

        if ($this->afterSaveRouteUrl) {
            $this->afterSaveRouteUrl[\MUtil_Model::REQUEST_ID] = $this->request->getParam(\MUtil_Model::REQUEST_ID);
        }
    }
}
