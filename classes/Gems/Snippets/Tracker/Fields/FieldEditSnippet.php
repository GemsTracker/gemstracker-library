<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: FieldEditSnippet.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Snippets\Tracker\Fields;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 11-mei-2015 15:26:36
 */
class FieldEditSnippet extends \Gems_Snippets_ModelFormSnippetGeneric
{
    /**
     * Set what to do when the form is 'finished'.
     *
     * @return \MUtil_Snippets_ModelFormSnippetAbstract (continuation pattern)
     */
    protected function setAfterSaveRoute()
    {
        parent::setAfterSaveRoute();

        if ($this->afterSaveRouteUrl) {
            $this->afterSaveRouteUrl[\MUtil_Model::REQUEST_ID] = $this->formData['gtf_id_track'];
            $this->afterSaveRouteUrl[\Gems_Model::FIELD_ID]    = $this->formData['gtf_id_field'];
            $this->afterSaveRouteUrl['sub']                    = $this->formData['sub'];
        }

        return $this;
    }
}
