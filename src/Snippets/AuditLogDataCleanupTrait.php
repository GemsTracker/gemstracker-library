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
        $output    = [];

        if (property_exists($this, 'csrfName')) {
            unset($newData[$this->csrfName]);
        }
        unset($newData['auto_form_focus_tracker'], $newData['__tmpEvenOut']);

        foreach ($this->extraNotLoggedFields as $field) {
            unset($newData[$field]);
        }

        if ($model) {
            $metaModel = $model->getMetaModel();

            if (method_exists($model, 'getKeyCopyName')) {
                foreach ($metaModel->getKeys() as $name) {
                    $copy = $model->getKeyCopyName($name);
                    unset($newData[$copy]);
                }
            }
        }

        // file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  print_r($newData, true) . "\n", FILE_APPEND);
        foreach ($newData as $name => $value) {
            if ($model && $metaModel->hasOnSave($name)) {
                $value = $metaModel->getOnSave($value, false, $name, $newData);
            }
            if ($value instanceof DateTimeInterface) {
                if ($model) {
                    $format = $metaModel->getWithDefault($name, 'storageFormat', 'c');
                } else {
                    $format = 'c';
                }
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