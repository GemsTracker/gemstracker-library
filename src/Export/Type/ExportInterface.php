<?php

namespace Gems\Export\Type;

use Gems\Export\ExportSettings\ExportSettingsInterface;
use MUtil\Form;
use Zalt\Model\MetaModelInterface;

interface ExportInterface
{
    function filterRow(MetaModelInterface $metaModel, array $row, ExportSettingsInterface|null $exportSettings): array;

    /**
     * form elements for extra options for this particular export option
     * @param  \MUtil\Form $form Current form to add the form elements
     * @param  array $data current options set in the form
     * @return array Form elements
     */
    public function getFormElements(Form &$form, array &$data): array;
}