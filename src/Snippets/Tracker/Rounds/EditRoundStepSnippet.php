<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker\Rounds;

use Gems\Legacy\CurrentUserRepository;
use Gems\Locale\Locale;
use Gems\Menu\MenuSnippetHelper;
use Gems\Repository\TrackDataRepository;
use Gems\Tracker;
use Gems\Tracker\Engine\StepEngineAbstract;
use Gems\Tracker\Snippets\EditRoundSnippetAbstract;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\MessengerInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class EditRoundStepSnippet extends EditRoundSnippetAbstract
{
    protected bool $selectedAutosubmit = true;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        MenuSnippetHelper $menuHelper,
        Tracker $tracker,
        TrackDataRepository $trackDataRepository,
        CurrentUserRepository $currentUserRepository,
        protected Locale $locale,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $menuHelper, $tracker, $trackDataRepository, $currentUserRepository);
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData(): array
    {
        parent::loadFormData();

        if ($this->trackEngine instanceof StepEngineAbstract) {
            if ($this->trackEngine->updateRoundModelToItem($this->getModel(), $this->formData, $this->locale->getLanguage())) {

                if (isset($this->formData[$this->saveButtonId])) {
                    // Disable validation & save
                    unset($this->formData[$this->saveButtonId]);

                    // Warn user
                    $this->addMessage($this->_('Lists choices changed.'));
                }
            }
        }
        return $this->formData;
    }
}
