<?php

/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * The organization model
 *
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Contains the organization
 *
 * Handles saving of the user definition config
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Model_OrganizationModel extends \Gems_Model_JoinModel
{
    /**
     *
     * @var array
     */
    protected $_styles;

    /**
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Constructor
     *
     * @param array|mixed $styles
     */
    public function __construct($styles = array())
    {
        parent::__construct('organization', 'gems__organizations', 'gor');

        $this->_styles = $styles;

        $this->setDeleteValues('gor_active', 0, 'gor_add_respondents', 0);
        $this->addColumn("CASE WHEN gor_active = 1 THEN '' ELSE 'deleted' END", 'row_class');

        \Gems_Model::setChangeFieldsByPrefix($this, 'gor');
    }

    /**
     * Set those settings needed for the browse display
     *
     *
     * @return \Gems_Model_OrganizationModel
     */
    public function applyBrowseSettings()
    {
        $dbLookup    = $this->util->getDbLookup();
        $definitions = $this->loader->getUserLoader()->getAvailableStaffDefinitions();
        $localized   = $this->util->getLocalized();
        $projectName = $this->project->getName();
        $yesNo       = $this->util->getTranslated()->getYesNo();

        $this->resetOrder();
        $this->set('gor_name',                  'label', $this->_('Name'), 'tab', $this->_('General'));
        $this->set('gor_location',              'label', $this->_('Location'));
        $this->set('gor_task',                  'label', $this->_('Task'),
                'description', sprintf($this->_('Task in %s project'), $projectName)
                );
        $this->set('gor_url',                   'label', $this->_('Url'));
        $this->setIfExists('gor_url_base',      'label', $this->_("Default url's"),
                'description', sprintf(
                        $this->_("Always switch to this organization when %s is accessed from one of these space separated url's. The first is used for mails."),
                        $projectName
                        )
                );
        $this->setIfExists('gor_code',             'label', $this->_('Organization code'),
                'description', $this->_('Optional code name to link the organization to program code.')
                );
        $this->set('gor_provider_id',           'label', $this->_('Healtcare provider id'),
                'description', $this->_('An interorganizational id used for import and export.')
                );

        $this->setIfExists('gor_active',        'label', $this->_('Active'),
                'description', $this->_('Can the organization be used?'),
                'multiOptions', $yesNo
                );

        $this->set('gor_contact_name',          'label', $this->_('Contact name'));
        $this->set('gor_contact_email',         'label', $this->_('Contact email'));

        // Determine order for details, but do not show in browse
        $this->set('gor_welcome');
        $this->set('gor_signature');
        $this->set('gor_create_account_template');
        $this->set('gor_reset_pass_template');


        $this->set('gor_has_login',             'label', $this->_('Login'),
                'description', $this->_('Can people login for this organization?'),
                'multiOptions', $yesNo
                );
        $this->set('gor_add_respondents',       'label', $this->_('Accepting'),
                'description', $this->_('Can new respondents be added to the organization?'),
                'multiOptions', $yesNo
                );
        $this->set('gor_has_respondents',       'label', $this->_('Respondents'),
                'description', $this->_('Does the organization have respondents?'),
                'multiOptions', $yesNo
                );
        $this->set('gor_respondent_group',      'label', $this->_('Respondent group'),
                'description', $this->_('Allows respondents to login.'),
                'multiOptions', $dbLookup->getAllowedRespondentGroups()
                );
        $this->set('gor_accessible_by',            'label', $this->_('Accessible by'),
                'description', $this->_('Checked organizations see this organizations respondents.'),
                'multiOptions', $dbLookup->getOrganizations()
                );
        $tp = new \MUtil_Model_Type_ConcatenatedRow(':', ', ');
        $tp->apply($this, 'gor_accessible_by');

        $this->setIfExists('gor_allowed_ip_ranges');

        if ($definitions && (count($definitions) > 1)) {
            $this->setIfExists('gor_user_class',    'label', $this->_('User Definition'),
                    'multiOptions', $definitions
                    );
        }

        $this->setIfExists('gor_iso_lang',      'label', $this->_('Language'),
                'multiOptions', $localized->getLanguages()
                );
        if ($this->_styles) {
            $this->setIfExists('gor_style',     'label', $this->_('Style'), 'multiOptions', $this->_styles);
        }

        return $this;
    }

    /**
     * Set those settings needed for the detailed display
     *
     * @return \Gems_Model_OrganizationModel
     */
    public function applyDetailSettings()
    {
        $staffTemplates = $this->getPasswordTemplatesFor('staffPassword');

        $this->applyBrowseSettings();

        $this->set('gor_welcome',                   'label', $this->_('Greeting'),
                'description', $this->_('For emails and token forward screen.'), 'elementClass', 'Textarea', 'rows', 5);
        $this->set('gor_signature',                 'label', $this->_('Signature'),
                'description', $this->_('For emails and token forward screen.'), 'elementClass', 'Textarea', 'rows', 5);
        $this->set('gor_create_account_template',   'label', $this->_('Create Account template'),
                'multiOptions', $staffTemplates);
        $this->set('gor_reset_pass_template',       'label', $this->_('Reset Password template'),
                'multiOptions', $staffTemplates);

        $this->setIfExists('gor_allowed_ip_ranges', 'label', $this->_('Allowed IP Ranges'),
            'description', $this->_('Separate with | example: 10.0.0.0-10.0.0.255 (subnet masks are not supported)'),
            'size', 50,
            'validator', new \Gems_Validate_IPRanges(),
            'maxlength', 500
            );


        if ($this->project->multiLocale) {
            $this->set('gor_name', 'description', 'ENGLISH please! Use translation file to translate.');
            $this->set('gor_url',  'description', 'ENGLISH link preferred. Use translation file to translate.');
        }

        return $this;
    }

    /**
     * Set those values needed for editing
     *
     * @return \Gems_Model_OrganizationModel
     */
    public function applyEditSettings()
    {
        $this->applyDetailSettings();
        $this->resetOrder();

        // GENERAL TAB
        $this->set('gor_name',
                'size', 25,
                'validator', $this->createUniqueValidator('gor_name')
                );
        $this->set('gor_location',
                'size', 50,
                'maxlength', 255
                );
        $this->set('gor_task',
                'size', 25);
        $this->set('gor_url',
                'size', 50
                );
        $this->setIfExists('gor_url_base',
                'size', 50,
                'filter', 'TrailingSlash'
                );
        $this->setIfExists('gor_code',
                'size', 10
                );
        $this->set('gor_provider_id');
        $this->setIfExists('gor_active',
                'elementClass', 'Checkbox'
                );

        // EMAIL TAB
        $this->set('gor_contact_name',              'tab', $this->_('Email') . ' & ' . $this->_('Token'),
                'order', $this->getOrder('gor_active') + 1000,
                'size', 25
                );
        $this->set('gor_contact_email',
                'size', 50,
                'validator', 'SimpleEmail'
                );
        $this->set('gor_welcome',
                'elementClass', 'Textarea',
                'rows', 5
                );
        $this->set('gor_signature',
                'elementClass', 'Textarea',
                'rows', 5
                );
        $this->set('gor_create_account_template');
        $this->set('gor_reset_pass_template');

        // ACCESS TAB
        $this->set('gor_has_login',                 'tab', $this->_('Access'),
                'order', $this->getOrder('gor_reset_pass_template') + 1000,
                'elementClass', 'CheckBox'
                );
        $this->set('gor_add_respondents',
                'elementClass', 'CheckBox'
                );
        $this->set('gor_has_respondents',
                'elementClass', 'Exhibitor'
                );
        $this->set('gor_respondent_group');
        $this->set('gor_accessible_by',
                'elementClass', 'MultiCheckbox'
                );
        $this->set('allowed',
                'label', $this->_('Can access'),
                'elementClass', 'Html'
                );

        $this->setIfExists('gor_allowed_ip_ranges',
                'size', 50,
                'validator', new \Gems_Validate_IPRanges(),
                'maxlength', 500
                );
        $this->setIfExists('gor_user_class');

        $definitions = $this->get('gor_user_class', 'multiOptions');
        if ($definitions && (count($definitions) > 1)) {
            reset($definitions);
            $this->setIfExists('gor_user_class',    'default', key($definitions), 'required', true);
        }

        // OTHER TAB
        $this->setIfExists('gor_iso_lang',  'tab', $this->_('Other'),
                'order', $this->getOrder('gor_user_class') + 1000,
                'default', $this->project->getLocaleDefault()
                );
        if ($this->_styles) {
            $this->setIfExists('gor_style');
        }
        return $this;
    }

    /**
     * Helper function to get the templates for mails
     *
     * @param string|array $for the template types to get
     * @return array
     */
    public function getPasswordTemplatesFor($for)
    {
        return $this->loader->getMailLoader()->getMailElements()->getAvailableMailTemplates(false, $for);
    }

    /**
     * Helper function that procesess the raw data after a load.
     *
     * @see \MUtil_Model_SelectModelPaginator
     *
     * @param mixed $data Nested array or \Traversable containing rows or iterator
     * @param boolean $new True when it is a new item
     * @param boolean $isPostData With post data, unselected multiOptions values are not set so should be added
     * @return array or \Traversable Nested
     */
    public function processAfterLoad($data, $new = false, $isPostData = false)
    {
        $data = parent::processAfterLoad($data, $new, $isPostData);

        if ($data instanceof \Traversable) {
            $data = iterator_to_array($data);
        }
        foreach ($data as &$row) {
            if (isset($row['gor_user_class']) && !empty($row['gor_user_class'])) {
                $definition = $this->loader->getUserLoader()->getUserDefinition($row['gor_user_class']);

                if ($definition instanceof \Gems_User_UserDefinitionConfigurableInterface && $definition->hasConfig()) {
                    $definition->addConfigFields($this);
                    $row = $row + $definition->loadConfig($row);
                }
            }

        }
        return $data;
    }

    /**
     * Save a single model item.
     *
     * Makes sure the password is saved too using the userclass
     *
     * @param array $newValues The values to store for a single model item.
     * @param array $filter If the filter contains old key values these are used
     * to decide on update versus insert.
     * @param array $saveTables Optional array containing the table names to save,
     * otherwise the tables set to save at model level will be saved.
     * @return array The values as they are after saving (they may change).
     */
    public function save(array $newValues, array $filter = null, array $saveTables = null)
    {
        //First perform a save
        $savedValues = parent::save($newValues, $filter, $saveTables);

        //Now check if we need to save config values
        if (isset($newValues['gor_user_class']) && !empty($newValues['gor_user_class'])) {
            $definition = $this->loader->getUserLoader()->getUserDefinition($newValues['gor_user_class']);

            if ($definition instanceof \Gems_User_UserDefinitionConfigurableInterface && $definition->hasConfig()) {
                $savedValues = $definition->saveConfig($savedValues, $newValues);

                if ($definition->getConfigChanged()>0 && $this->getChanged()<1) {
                    $this->setChanged(1);
                }
            }
        }

        return $savedValues;
    }
}