<?php

/**
 *
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2021, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Model;

use Gems\Util\SiteUtil;

/**
 *
 * @package    Gems
 * @subpackage Model
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class SiteModel extends  \Gems_Model_JoinModel
{
    /**
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * @var \Gems_Util
     */
    protected $util;
    
    /**
     * Create a model that joins two or more tables
     */
    public function __construct()
    {
        parent::__construct('gems__sites', 'gems__sites', 'gsi', true);

        $this->addColumn(
            new \Zend_Db_Expr("CASE WHEN gsi_active = 1 THEN '' ELSE 'deleted' END"),
            'row_class'
        );
    }

    /**
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @param int $defaultOrgId The default organization id or null if current organization
     * @return \Gems_Model_StaffModel
     */
    public function applySettings($detailed, $action)
    {
        $yesNo = $this->util->getTranslated()->getYesNo();

        $this->set('gsi_url', 'label', $this->_('Site'),
            'validators[unique]', $this->createUniqueValidator('gsi_url'));
        $this->set('gsi_order', 'label', $this->_('Priority'));

        $this->set('gsi_select_organizations', 'label', $this->_('Select organizations?'),
                   'description', $this->_('Use this site only for selected organizations?'),
                   'elementClass', 'Checkbox',
                   'multiOptions', $yesNo
        );
        $this->set('gsi_organizations', 'label', $this->_('Organizations'),
                   'description', $this->_('The organizations that use the url.'),
                   'elementClass', 'MultiCheckbox',
                   'multiOptions', $this->util->getDbLookup()->getOrganizations(),
                   'required', true
        );
        $ct = new \MUtil_Model_Type_ConcatenatedRow(SiteUtil::ORG_SEPARATOR, $this->_(', '), true);
        $ct->apply($this, 'gsi_organizations');

        $switches = array(
            0 => array(
                'gsi_organizations' => array('elementClass' => 'Hidden', 'label' => null),
            ),
        );
        $this->addDependency(array('ValueSwitchDependency', $switches), 'gsi_select_organizations');

        $styles = null;
        // TODO: Add styles in a different way!
        if ($styles) {
            $this->set('gsi_style', 'label', $this->_('Style'),
                       'multiOptions', $styles
            );
            
            $default = $this->get('gsi_style', 'default');
            if (! in_array($default, $styles)) {
                reset($styles);
                $this->set('gsi_style', 'default', $default);
            }
        }

        $this->set('gsi_iso_lang', 'label', $this->_('Initial language'),
                   'default', $this->project->getLocaleDefault() ?: 'en',
                   'multiOptions', $this->util->getLocalized()->getLanguages()
        );

        $this->set('gsi_active', 'label', $this->_('Active'),
                   'description', $this->_('Is the site in use?'),
                   'multiOptions', $yesNo
        );
        $this->setIfExists('gsi_blocked', 'label', $this->_('Block'),
                   'description', $this->_('Block this site if used.'),
                   'multiOptions', $yesNo
        );
    }
}