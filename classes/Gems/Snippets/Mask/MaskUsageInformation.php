<?php

/**
 * @package    Gems
 * @subpackage Snippets]\Mask
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets\Mask;

use Zalt\Snippets\TranslatableSnippetAbstract;

/**
 * @package    Gems
 * @subpackage Snippets]\Mask
 * @since      Version 2.0
 */

class MaskUsageInformation extends TranslatableSnippetAbstract
{
    public function getHtmlOutput()
    {
        $seq = $this->getHtmlSequence();
        $seq->br();
        $div = $seq->div(['class' => 'alert alert-info', 'role' => "alert"]);

        $div->h2($this->_('Explanation of mask usage'), ['style' => 'margin-top: 5px;']);

        $div->pInfo($this->_('Mask hide privacy sensitive information for certain groups of users.'));

        $div->h3($this->_('Using masks'));

        $ul = $div->ul();
        $ul->li($this->_('The mask are searched through in the Order specified per mask.'));
        $ul->li($this->_('The first mask (the one with the lowest Order) that covers the current user is used.'));
        $ul->li($this->_('Enforced masks use the original base organization and group of a user as well as the currently selected ones.'));
        $ul->li($this->_('Multiple mask may have the same order, but then this may result in random mask selection.'));

        $div->h3($this->_('An example'));

        $ul = $div->ul();
        $ul->li($this->_('Mask order 10: show all for Organization 1.'));
        $ul->li($this->_('Mask order 20: hide all for Group Super admins, enforced.'));
        $ul->li($this->_('Mask order 30: hide most for Group Local admins.'));
        $ul->li($this->_('Mask order 40: hide all for Organization 2, enforced.'));

        $div->pInfo($this->_('Using these 4 masks, we expect these outcomes.'));

        $ul = $div->ul();
        $ul->li($this->_('Any user switched to Organization 1 can see all.'));
        $ul->li($this->_('Any Super admin sees nothing, unless switched to Organization 1.'));
        $ul->li($this->_('Any Local admin sees something but not all, except when switched to Organization 1 or to a different group.'));
        $ul->li($this->_('Anyone from Organization 2 sees nothing, except for Local admins or the user switched to Organization 1.'));

        return $seq;
    }
}
