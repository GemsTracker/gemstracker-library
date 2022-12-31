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

use Gems\Html;
use Zalt\Html\AElement;
use Zalt\Late\Late;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelBridge\TableBridge;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Organization
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class OrganizationTableSnippet extends \Gems\Snippets\ModelTableSnippet
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = ['gor_name' => SORT_ASC];

    protected function addBrowseTableColumns(TableBridge $bridge, DataReaderInterface $model)
    {
        $bridge->tr()->class = $bridge->row_class;

        $showMenuItems = $this->getShowUrls($bridge);
        foreach ($showMenuItems as $keyOrLabel => $menuItem) {
            $showLabel = $keyOrLabel;
            if (is_int($showLabel)) {
                $showLabel = $this->_('Show');
            }

            $bridge->addItemLink(Html::actionLink($menuItem, $showLabel));
        }

        $br = Html::create()->br();

        $orgName[] = Late::iff($bridge->gor_url,
            AElement::a($bridge->gor_name, array('href' => $bridge->gor_url, 'target' => '_blank', 'class' => 'globe')),
            $bridge->gor_name);
        $orgName[] = $bridge->createSortLink('gor_name');

        $mailName[] = Late::iff($bridge->gor_contact_email,
            AElement::email(Late::first($bridge->gor_contact_name, $bridge->gor_contact_email), array('href' => array('mailto:', $bridge->gor_contact_email))),
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
            $bridge->addItemLink(Html::actionLink($menuItem, $editLabel));
        }
    }
}
