<?php

/**
 *
 * @package    Gems
 * @subpackage Model_Translator
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Model\Translator;

/**
 *
 *
 * @package    Gems
 * @subpackage Model_Translator
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2
 */
class StaffTranslator extends \Gems\Model\Translator\StraightTranslator
{
    /**
     * The name of the field to store the organization id in
     *
     * @var string
     */
    protected $orgIdField = 'gsf_id_organization';
    
    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;
    
    /**
     *
     * @var \Gems\User\Organization
     */
    protected $_organization;
        
    public function afterRegistry() {
        parent::afterRegistry();
    
        // The users current organization
        $this->_organization = $this->loader->getCurrentUser()->getCurrentOrganization();
    }

    /**
     * Add organization id and gul_user_class when needed
     *
     * @param mixed $row array or \Traversable row
     * @param scalar $key
     * @return mixed Row array or false when errors occurred
     */
    public function translateRowValues($row, $key)
    {
        $row = parent::translateRowValues($row, $key);

        if (! $row) {
            return false;
        }
        
        // Default to active and can login
        if (!isset($row['gsf_active'])) {
            $row['gsf_active'] = 1;
        }        
        if (!isset($row['gul_can_login'])) {
            $row['gul_can_login'] = 1;
        }
        
        // Make sure we have an organization
        if (!isset($row['gsf_id_organization'])) {
            $row['gsf_id_organization'] = $this->_organization->getId();
        }
        
        // Use organization default userclass is not specified
        if (!isset($row['gul_user_class'])) {
            $row['gul_user_class'] = $this->loader->getUserLoader()->getOrganization($row['gsf_id_organization'])->get('gor_user_class');
        }

        return $row;
    }
}