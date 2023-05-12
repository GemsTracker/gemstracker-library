<?php

/**
 *
 * @package    Gems
 * @subpackage Screens\Respondent\Unsubscribe
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Screens\Respondent\Unsubscribe;

use Gems\Screens\UnsubscribeScreenInterface;

/**
 *
 * @package    Gems
 * @subpackage Screens\Respondent\Unsubscribe
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 19-Mar-2019 11:41:08
 */
class EmailOnlyUnsubscribe extends \MUtil\Translate\TranslateableAbstract implements UnsubscribeScreenInterface
{
    /**
     * Use currentUser since currentOrganization may have changed by now
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil\Html\HtmlElement
     */
    public function getScreenLabel()
    {
        return $this->_('Unsubscribe using e-mail address only');
    }

    /**
     *
     * @return array Added before all other parameters
     */
    public function getUnsubscribeParameters()
    {
        return [
            'formTitle' => sprintf(
                    $this->_('Unsubscribe from surveys for %s'),
                    $this->currentUser->getCurrentOrganization()->getName()
                    ),
            'routeAction' => 'unsubscribe-thanks',
            'saveLabel' => $this->_('Unsubscribe'),
        ];
    }

    /**
     *
     * @return array Of snippets
     */
    public function getUnsubscribeSnippets()
    {
        return ['Unsubscribe\\EmailUnsubscribeSnippet'];
    }
}
