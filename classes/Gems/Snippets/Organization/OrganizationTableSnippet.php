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
class OrganizationTableSnippet extends \Gems\Snippets\ModelTableSnippetGeneric
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
     * @param \MUtil\Model\Bridge\TableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(\MUtil\Model\Bridge\TableBridge $bridge, \MUtil\Model\ModelAbstract $model)
    {
        $bridge->tr()->class = $bridge->row_class;

        $showMenuItems = $this->getShowUrls($bridge);
        foreach ($showMenuItems as $keyOrLabel => $menuItem) {
            $showLabel = $keyOrLabel;
            if (is_int($showLabel)) {
                $showLabel = $this->_('Show');
            }

            $bridge->addItemLink(\Gems\Html::actionLink($menuItem, $showLabel));
        }

        // make sure search results are highlighted
        $this->applyTextMarker();

        $br = \MUtil\Html::create()->br();

        $orgName[] = \MUtil\Lazy::iff($bridge->gor_url,
            \MUtil\Html\AElement::a($bridge->gor_name, array('href' => $bridge->gor_url, 'target' => '_blank', 'class' => 'globe')),
            $bridge->gor_name);
        $orgName[] = $bridge->createSortLink('gor_name');

        $mailName[] = \MUtil\Lazy::iff($bridge->gor_contact_email,
            \MUtil\Html\AElement::email(\MUtil\Lazy::first($bridge->gor_contact_name, $bridge->gor_contact_email), array('href' => array('mailto:', $bridge->gor_contact_email))),
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


        $editMenuItems = $this->getEditUrls($bridge);
        foreach ($editMenuItems as $keyOrLabel => $menuItem) {
            $editLabel = $keyOrLabel;
            if (is_int($editLabel)) {
                $editLabel = $this->_('Edit');
            }
            $bridge->addItemLink(\Gems\Html::actionLink($menuItem, $editLabel));
        }
    }
}
