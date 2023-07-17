<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker\Field
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Field;

use Gems\Repository\ConsentRepository;
use Gems\Util\Translated;
use MUtil\Translate\Translator;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker\Field
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 11-mei-2015 19:01:10
 */
class ConsentField extends FieldAbstract
{
    public function __construct(
        int $trackId,
        string $fieldKey,
        array $fieldDefinition,
        Translator $translator,
        Translated $translatedUtil,
        protected readonly ConsentRepository $consentRepository,
    ) {
        parent::__construct($trackId, $fieldKey, $fieldDefinition, $translator, $translatedUtil);
    }

    /**
     * Add the model settings like the elementClass for this field.
     *
     * elementClass is overwritten when this field is read only, unless you override it again in getDataModelSettings()
     *
     * @param array $settings The settings set so far
     */
    protected function addModelSettings(array &$settings): void
    {
        $empty = $this->translatedUtil->getEmptyDropdownArray();

        $settings['elementClass'] = 'Select';
        $settings['multiOptions'] = $empty + $this->consentRepository->getUserConsentOptions();
    }
}
