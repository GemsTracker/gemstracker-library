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

use Gems\Legacy\CurrentUserRepository;
use Gems\Screens\UnsubscribeScreenInterface;
use Zalt\Base\TranslatorInterface;

/**
 *
 * @package    Gems
 * @subpackage Screens\Respondent\Unsubscribe
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 19-Mar-2019 11:41:08
 */
class EmailOnlyUnsubscribe implements UnsubscribeScreenInterface
{
    public function __construct(
        protected readonly TranslatorInterface $translator,
        protected readonly CurrentUserRepository $currentUserRepository,
    )
    {}

    /**
     *
     * @inheritDoc
     */
    public function getScreenLabel(): string
    {
        return $this->translator->_('Unsubscribe using e-mail address only');
    }

    /**
     *
     * @return array Added before all other parameters
     */
    public function getUnsubscribeParameters(): array
    {
        return [
            'formTitle' => sprintf(
                    $this->translator->_('Unsubscribe from surveys for %s'),
                    $this->currentUserRepository->getCurrentOrganization()->getName()
                    ),
            'routeAction' => 'participate.unsubscribe-thanks',
            'saveLabel' => $this->translator->_('Unsubscribe'),
        ];
    }

    /**
     *
     * @return array Of snippets
     */
    public function getUnsubscribeSnippets(): array
    {
        return ['Unsubscribe\\EmailUnsubscribeSnippet'];
    }
}
