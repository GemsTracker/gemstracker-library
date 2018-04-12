<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Organization
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: OrganizationTableSnippet.php 203 2011-07-07 12:51:32Z matijs $
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
class OrganizationTableSnippet extends \Gems_Snippets_ModelTableSnippetGeneric
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = 'gor_name';

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $bridge->tr()->class = $bridge->row_class;

        if ($showMenuItem = $this->getShowMenuItem()) {
            $bridge->addItemLink($showMenuItem->toActionLinkLower($this->request, $bridge));
        }

        // make sure search results are highlighted
        $this->applyTextMarker();

        $br = \MUtil_Html::create()->br();

        $orgName[] = \MUtil_Lazy::iff($bridge->gor_url,
                \MUtil_Html_AElement::a($bridge->gor_name, array('href' => $bridge->gor_url, 'target' => '_blank', 'class' => 'globe')),
                $bridge->gor_name);
        $orgName[] = $bridge->createSortLink('gor_name');

        $mailName[] = \MUtil_Lazy::iff($bridge->gor_contact_email,
                \MUtil_Html_AElement::email(\MUtil_Lazy::first($bridge->gor_contact_name, $bridge->gor_contact_email), array('href' => array('mailto:', $bridge->gor_contact_email))),
                $bridge->gor_contact_name);
        $mailName[] = $bridge->createSortLink('gor_contact_name');

        $bridge->addMultiSort($orgName, $br, 'gor_task', $br, 'gor_location');
        $bridge->addMultiSort($mailName, $br, 'gor_active', $br, 'gor_has_login');
        if ($model->has('gor_respondent_group', 'label')) {
            $bridge->addMultiSort('gor_add_respondents', $br, 'gor_has_respondents', $br, 'gor_respondent_group');
        } else {
            $bridge->addMultiSort('gor_add_respondents', $br, 'gor_has_respondents');
        }
        $bridge->add('gor_accessible_by');

        if ($editMenuItem = $this->getEditMenuItem()) {
            $bridge->addItemLink($editMenuItem->toActionLinkLower($this->request, $bridge));
        }
    }
}
