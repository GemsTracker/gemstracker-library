<?php

/**
 *
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Model;

use Gems\Legacy\CurrentUserRepository;
use Gems\User\User;
use MUtil\Translate\Adapter\Potemkin;

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
class HiddenOrganizationModel extends MaskedModel
{
    /**
     * @var CurrentUserRepository
     */
    protected $currentUserRepository;

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
            if (isset($parameters[\MUtil\Model::REQUEST_ID]) && (! isset($parameters[\MUtil\Model::REQUEST_ID1], $parameters[\MUtil\Model::REQUEST_ID2]))) {

                $id    = $parameters[\MUtil\Model::REQUEST_ID];
                $keys  = $this->getKeys();
                $field = array_shift($keys);

                $parameters[$field] = $id;
            }

            $currentUser = $this->currentUserRepository->getCurrentUser();

            if (isset($parameters[\MUtil\Model::REQUEST_ID2]) && $currentUser instanceof User &&
                (! array_key_exists($parameters[\MUtil\Model::REQUEST_ID2], $this->currentUser->getAllowedOrganizations()))) {

                throw new \Gems\Exception(
                        $this->_('Inaccessible or unknown organization'),
                        403, null,
                        sprintf($this->_('Access to this page is not allowed for current role: %s.'), $this->currentUser->getRole()));
            }

            return parent::applyParameters($parameters, $includeNumericFilters);
        }

        return array();
    }

    /**
     * The current organization id of the current user
     *
     * @return int
     */
    public function getCurrentOrganization()
    {
        return $this->currentUserRepository->getCurrentOrganizationId();
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
            if ($forData instanceof \MUtil\Lazy\RepeatableInterface) {
                // Here I kind of assume that the data always contains the organization key.
                $organizationInKey = true;
            } else {
                $ordId = $this->getCurrentOrganization();
                $organizationInKey = self::_getValueFrom('gr2o_id_organization', $forData) == $ordId;
            }
        }

        if ($organizationInKey) {
            $href[\MUtil\Model::REQUEST_ID]  = self::_getValueFrom(reset($keys), $forData);
        } else {
            $href[\MUtil\Model::REQUEST_ID1] = self::_getValueFrom(reset($keys), $forData);
            next($keys);
            $href[\MUtil\Model::REQUEST_ID2] = self::_getValueFrom(key($keys), $forData);
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
            $this->translate = new Potemkin();
        }

        return $this->translate;
    }

    /**
     * Helper function that procesess the raw data after a load.
     *
     * @see \MUtil\Model\SelectModelPaginator
     *
     * @param mixed $data Nested array or \Travers (able containing rows or iterator
     * @param boolean $new True when it is a new item
     * @param boolean $isPostData With post data, unselected multiOptions values are not set so should be added
     * @return array or \Traversable Nested
     */
    public function processAfterLoad($data, $new = false, $isPostData = false): mixed
    {
        if (isset($this->maskRepository)) {
            // Repeat settings here, because the might be overloaded in the meantime
            $this->applyMask();
        }

        return parent::processAfterLoad($data, $new, $isPostData);
    }
}