<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Organization
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Organization;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Organization
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class OrganizationEditSnippet extends \Gems_Snippets_ModelTabFormSnippetGeneric
{
    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

   /**
     * Hook that allows actions when data was saved
     *
     * When not rerouted, the form will be populated afterwards
     *
     * @param int $changed The number of changed rows (0 or 1 usually, but can be more)
     */
    protected function afterSave($changed)
    {
         parent::afterSave($changed);

        // Make sure any changes in the allowed list are reflected.
        $this->currentUser->refreshAllowedOrganizations();
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData()
    {
        parent::loadFormData();

        if (isset($this->formData['gor_id_organization']) && $this->formData['gor_id_organization']) {
            $model = $this->getModel();

            // Strip self from list of organizations
            $multiOptions = $model->get('gor_accessible_by', 'multiOptions');
            unset($multiOptions[$this->formData['gor_id_organization']]);
            $model->set('gor_accessible_by', 'multiOptions', $multiOptions);

            // Show allowed organisations
            $org         = $this->loader->getOrganization($this->formData['gor_id_organization']);
            $allowedOrgs = $org->getAllowedOrganizations();
            //Strip self
            unset($allowedOrgs[$this->formData['gor_id_organization']]);
            $display = join(', ', $allowedOrgs);
            if (! $display) {
                $display = \MUtil_Html::create('em', $this->_('No access to other organizations.'));
            }
            $this->formData['allowed'] = $display;
            $model->set('allowed', 'value', $display);
        }
    }
}