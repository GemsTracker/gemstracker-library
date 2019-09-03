<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Staff
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Staff;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Staff
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 03-Sep-2019 13:15:52
 */
class SystemUserCreateEditSnippet extends StaffCreateEditSnippet
{
    /**
     * Hook containing the actual save code.
     *
     * Call's afterSave() for user interaction.
     *
     * @see afterSave()
     */
    protected function saveData()
    {
        parent::saveData();

        if (isset($this->formData['gul_two_factor_key'], $this->formData['gsf_id_user']) &&
                $this->formData['gul_two_factor_key']) {

            $user = $this->loader->getUserLoader()->getUserByStaffId($this->formData['gsf_id_user']);

            if ($user->canSetPassword()) {
                $this->addMessage(sprintf($this->_('Password saved for: %s'), $user->getLoginName()));
                $user->setPassword($this->formData['gul_two_factor_key']);
            }
        }
    }
}
