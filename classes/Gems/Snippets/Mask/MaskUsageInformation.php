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

        $div->pInfo($this->_('Masks hide privacy sensitive information for certain groups of users.'));

        $div->h3($this->_('Using masks'));

        $ul = $div->ul();
        $ul->li($this->_('The mask are searched through in the Order specified per mask.'));
        $ul->li($this->_('The first mask (the one with the lowest Order) that covers the current user is used.'));
        $ul->li($this->_('All masks are compared using the currently selected group and organization.'));
        $ul->li($this->_('Enforced masks are ALSO compared using users base organization and group.'));
        $ul->li($this->_('Multiple mask may have the same order.'), ' ')->em($this->_('This may result in the mask selection being random when two mask match!'));

        $div->h3($this->_('An example'));

        $ul = $div->ul();
        $ul->li($this->_('Mask order 10: shows all for Organization 1.'));
        $ul->li($this->_('Mask order 20: hides all for Group Super admins, enforced.'));
        $ul->li($this->_('Mask order 30: hides most for Group Local admins.'));
        $ul->li($this->_('Mask order 40: hides all for Organization 2, enforced.'));

        $div->pInfo($this->_('Using these 4 masks, we can expect these outcomes.'));

        $ul = $div->ul();
        $ul->li($this->_('Any user switched to Organization 1 can see all.'));
        $ul->li($this->_('Any Super admin sees nothing, unless switched to Organization 1.'));
        $ul->li($this->_('Any Local admin sees something but not all, except when switched to Organization 1 or to a different group.'));
        $ul->li($this->_('Anyone from Organization 2 sees nothing, except for Local admins or when switched to Organization 1.'));

        return $seq;
    }
}
