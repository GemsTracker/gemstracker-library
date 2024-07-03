<?php

namespace Gems\Tracker\Model;

use Gems\Tracker;
use Zalt\Model\MetaModel;
use Zalt\Model\MetaModelLoader;

class FieldMaintenanceMetaModel extends MetaModel
{
    public bool $addLoadDependency = false;

    public function __construct(
        string $modelName,
        MetaModelLoader $modelLoader,
        protected readonly Tracker $tracker,
        protected readonly string $modelField,
        protected readonly array $dependencies,
    )
    {
        parent::__construct($modelName, $modelLoader);
    }

    public function processRowAfterLoad(array $row, $new = false, $isPost = false, &$transformColumns = array())
    {
        if ($this->addLoadDependency && !empty($row['gtf_field_type'])) {
            if (! isset($row[$this->modelField])) {
                $row[$this->modelField] = $this->getModelNameForRow($row);
            }

            // Now add the type specific dependency (if any)
            $class = $this->getTypeDependencyClass($row['gtf_field_type']);
            if ($class) {
                $dependency = $this->tracker->createTrackClass($class, (int)$row['gtf_id_track']);
                $this->addDependency($dependency, null, null, 'row');
            }
        }

        return parent::processRowAfterLoad(
            $row,
            $new,
            $isPost,
            $transformColumns
        );
    }

    /**
     * Get the name of the union model that should be used for this row.
     *
     * @param array $row
     * @return string
     */
    public function getModelNameForRow(array $row): string
    {
        if (isset($row['gtf_field_type']) && ('appointment' === $row['gtf_field_type'])) {
            return FieldMaintenanceModel::APPOINTMENTS_NAME;
        }
        if ((! isset($row['gtf_field_type'])) && isset($row[$this->modelField]) && $row[$this->modelField]) {
            return $row[$this->modelField];
        }
        return FieldMaintenanceModel::FIELDS_NAME;
    }

    /**
     * Get the dependency class name (if any)
     *
     * @param string $fieldType
     * @return string|null Classname including Model\Dependency\ part
     */
    public function getTypeDependencyClass(string $fieldType): string|null
    {
        if (isset($this->dependencies[$fieldType]) && $this->dependencies[$fieldType]) {
            if (class_exists($this->dependencies[$fieldType])) {
                return $this->dependencies[$fieldType];
            }
            return 'Model\\Dependency\\' . $this->dependencies[$fieldType];
        }
        return null;
    }
}