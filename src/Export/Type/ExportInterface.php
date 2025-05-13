<?php

namespace Gems\Export\Type;

use Gems\Export\ExportSettings\ExportSettingsInterface;
use MUtil\Form;
use Zalt\Model\MetaModelInterface;

interface ExportInterface
{
    public function filterRow(MetaModelInterface $metaModel, array $row, array|null $exportSettings): array;

    /**
     * @return array Default values in form
     */
    public function getDefaultFormValues(): array;

    /**
     * form elements for extra options for this particular export option
     * @param  \MUtil\Form $form Current form to add the form elements
     * @param  array $data current options set in the form
     * @return array Form elements
     */
    public function getFormElements(Form &$form, array &$data): array;

    public function getHelpInfo(): array;

    public function getName(): string;
}