<?php

/**
 *
 * @package    Gems
 * @subpackage Model_Translator
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Model_Translator
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2
 */
class Gems_Model_Translator_StaffTranslator extends \Gems_Model_Translator_StraightTranslator
{
    /**
     * The name of the field to store the organization id in
     *
     * @var string
     */
    protected $orgIdField = 'gsf_id_organization';
    
    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;
    
    /**
     *
     * @var \Gems_User_Organization
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

        if (!isset($row['gsf_id_organization'])) {
            $row['gsf_id_organization'] = $this->_organization->getId();
            
            if (!isset($row['gul_user_class'])) {
                $row['gul_user_class'] = $this->_organization->get('gor_user_class');
            }
        } elseif (!isset($row['gul_user_class'])) {
            $row['gul_user_class'] = $this->loader->getUserLoader()->getOrganization($row['gsf_id_organization'])->get('gor_user_class');
        }

        return $row;
    }
}