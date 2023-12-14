<?php

declare(strict_types=1);


/**
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets;

use DateTimeInterface;
use Zalt\Model\Data\DataWriterInterface;

/**
 * @package    Gems
 * @subpackage Snippets
 * @since      Class available since version 1.0
 */
trait AuditLogDataCleanupTrait
{
    /**
     * @var array|string[] Fields that should not be logged
     */
    protected array $extraNotLoggedFields = [];

    public function cleanupLogData(array $newData, DataWriterInterface $model = null): array
    {
        $metaModel = $model->getMetaModel();
        $output    = [];

        unset($newData[$this->csrfName], $newData['auto_form_focus_tracker'], $newData['__tmpEvenOut']);

        foreach ($this->extraNotLoggedFields as $field) {
            unset($newData[$field]);
        }

        if ($model && method_exists($model,'getKeyCopyName')) {
            foreach($model->getMetaModel()->getKeys() as $name) {
                $copy = $model->getKeyCopyName($name);
                unset($newData[$copy]);
            }
        }

        // file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  print_r($newData, true) . "\n", FILE_APPEND);
        foreach ($newData as $name => $value) {
            if ($metaModel->hasOnSave($name)) {
                $value = $metaModel->getOnSave($value, false, $name, $newData);
            }
            if ($value instanceof DateTimeInterface) {
                $format = $metaModel->getWithDefault($name, 'storageFormat', 'c');
                $value = $value->format($format);
            }
            if (is_object($value)) {
                $value = get_class($value);
            }
            $output[$name] = $value;
        }
        // file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  print_r($output, true) . "\n", FILE_APPEND);

        return $output;
    }
}