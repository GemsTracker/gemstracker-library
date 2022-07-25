<?php

/**
 *
 * @package    Gems
 * @subpackage Menu
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Menu;

/**
 * A class that hides the current organization when it is specified as parameter
 *
 * @package    Gems
 * @subpackage Menu
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.5
 */
class HiddenOrganizationHrefAttribute extends \MUtil\Html\HrefArrayAttribute
{
    private $_hiddenOrgId;

    /**
     * Returns the rendered values of th earray elements
     *
     * @return array
     */
    protected function _getArrayRendered()
    {
        $results = parent::_getArrayRendered();

        if (isset($results[\MUtil\Model::REQUEST_ID1], $results[\MUtil\Model::REQUEST_ID2]) &&
                ($results[\MUtil\Model::REQUEST_ID2] == $this->_hiddenOrgId)) {

            $results[\MUtil\Model::REQUEST_ID] = $results[\MUtil\Model::REQUEST_ID1];
            unset($results[\MUtil\Model::REQUEST_ID1], $results[\MUtil\Model::REQUEST_ID2]);
        }

        return $results;
    }

    /**
     * The organization id that should not be displayed.
     *
     * @param int $orgId Organization id
     */
    public function setHiddenOrgId($orgId) {
        $this->_hiddenOrgId = $orgId;
    }
}
