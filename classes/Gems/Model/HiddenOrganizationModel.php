<?php

/**
 *
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

use Gems\User\Group;

/**
 * Extension of JoinModel for models where the organization id is
 * part of the key, but left out of the request.
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Model_HiddenOrganizationModel extends \Gems_Model_JoinModel
{
    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->refreshGroupSettings();
   }

    /**
     * Stores the fields that can be used for sorting or filtering in the
     * sort / filter objects attached to this model.
     *
     * @param array $parameters
     * @param boolean $includeNumericFilters When true numeric filter keys (0, 1, 2...) are added to the filter as well
     * @return array The $parameters minus the sort & textsearch keys
     */
    public function applyParameters(array $parameters, $includeNumericFilters = false)
    {
        if ($parameters) {
            // Allow use when passed only an ID value
            if (isset($parameters[\MUtil_Model::REQUEST_ID]) && (! isset($parameters[\MUtil_Model::REQUEST_ID1], $parameters[\MUtil_Model::REQUEST_ID2]))) {

                $id    = $parameters[\MUtil_Model::REQUEST_ID];
                $keys  = $this->getKeys();
                $field = array_shift($keys);

                $parameters[$field] = $id;

                if ($field2 = array_shift($keys)) {
                    $parameters[$field2] = $this->getCurrentOrganization();
                    \MUtil_Echo::r('Still using old HiddenModel parameters.', 'DEPRECIATION WARNING');
                    \MUtil_Echo::r($parameters);
                }

                unset($parameters[\MUtil_Model::REQUEST_ID]);
            }

            if (isset($parameters[\MUtil_Model::REQUEST_ID2]) &&
                (! array_key_exists($parameters[\MUtil_Model::REQUEST_ID2], $this->currentUser->getAllowedOrganizations()))) {

                $this->initTranslateable();

                throw new \Gems_Exception(
                        $this->_('Inaccessible or unknown organization'),
                        403, null,
                        sprintf($this->_('Access to this page is not allowed for current role: %s.'), $this->currentUser->getRole()));
            }

            return parent::applyParameters($parameters, $includeNumericFilters);
        }

        return array();
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return ($this->currentUser instanceof \Gems_User_User);
    }

    /**
     * The current organization id of the current user
     *
     * @return int
     */
    public function getCurrentOrganization()
    {
        return $this->currentUser->getCurrentOrganizationId();
    }

    /**
     * Return an identifier the item specified by $forData
     *
     * basically transforms the fieldnames ointo oan IDn => value array
     *
     * @param mixed $forData Array value to vilter on
     * @param array $href Or \ArrayObject
     * @return array That can by used as href
     */
    public function getKeyRef($forData, $href = array(), $organizationInKey = null)
    {
        $keys = $this->getKeys();

        if (! $organizationInKey) {
            if ($forData instanceof \MUtil_Lazy_RepeatableInterface) {
                // Here I kind of assume that the data always contains the organization key.
                $organizationInKey = true;
            } else {
                $ordId = $this->getCurrentOrganization();
                $organizationInKey = self::_getValueFrom('gr2o_id_organization', $forData) == $ordId;
            }
        }

        if ($organizationInKey) {
            $href[\MUtil_Model::REQUEST_ID]  = self::_getValueFrom(reset($keys), $forData);
        } else {
            $href[\MUtil_Model::REQUEST_ID1] = self::_getValueFrom(reset($keys), $forData);
            next($keys);
            $href[\MUtil_Model::REQUEST_ID2] = self::_getValueFrom(key($keys), $forData);
        }

        return $href;
    }

    /**
     * Returns a translate adaptor
     *
     * @return \Zend_Translate_Adapter
     */
    protected function getTranslateAdapter()
    {
        if ($this->translate instanceof \Zend_Translate)
        {
            return $this->translate->getAdapter();
        }

        if (! $this->translate instanceof \Zend_Translate_Adapter) {
            $this->translate = new \MUtil_Translate_Adapter_Potemkin();
        }

        return $this->translate;
    }

    /**
     * Helper function that procesess the raw data after a load.
     *
     * @see \MUtil_Model_SelectModelPaginator
     *
     * @param mxied $data Nested array or Traversable containing rows or iterator
     * @param boolean $new True when it is a new item
     * @param boolean $isPostData With post data, unselected multiOptions values are not set so should be added
     * @return array or Traversable Nested
     */
    public function processAfterLoad($data, $new = false, $isPostData = false)
    {
        // Repeat settings here, because the might be overloaded in the meantime
        $this->refreshGroupSettings();

        return parent::processAfterLoad($data, $new, $isPostData);
    }

    /**
     * Function te re-apply all the masks and settings for the current group
     *
     * @return void
     */
    protected function refreshGroupSettings()
    {
        $group = $this->currentUser->getGroup();
        if ($group instanceof Group) {
            $group->applyGroupToModel($this);
        }
    }
}