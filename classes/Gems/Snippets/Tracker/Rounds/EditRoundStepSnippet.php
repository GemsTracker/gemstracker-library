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

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class EditRoundStepSnippet extends \Gems\Tracker\Snippets\EditRoundSnippetAbstract
{
    /**
     *
     * @var \Zend_Locale
     */
    protected $locale;

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData()
    {
        parent::loadFormData();

        if ($this->trackEngine instanceof \Gems\Tracker\Engine\StepEngineAbstract) {
            if ($this->trackEngine->updateRoundModelToItem($this->getModel(), $this->formData, $this->locale->getLanguage())) {

                if (isset($this->formData[$this->saveButtonId])) {
                    // Disable validation & save
                    unset($this->formData[$this->saveButtonId]);

                    // Warn user
                    $this->addMessage($this->_('Lists choices changed.'));
                }
            }
        }
    }
}
