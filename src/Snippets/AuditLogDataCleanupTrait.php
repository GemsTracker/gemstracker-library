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

        foreach ($newData as $name => $value) {
            if ($value instanceof DateTimeInterface) {
                $newData[$name] = $value->format('c');
            }
            if (is_object($value)) {
                $newData[$name] = get_class($value);
            }
        }

        return $newData;
    }
}