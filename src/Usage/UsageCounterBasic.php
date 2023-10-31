<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Usage
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Usage;

use Gems\Db\ResultFetcher;
use Gems\Model\Setup\ConsentUsageCounter;
use Zalt\Base\TranslateableTrait;
use Zalt\Base\TranslatorInterface;
use Zalt\Snippets\DeleteModeEnum;

/**
 * @package    Gems
 * @subpackage Usage
 * @since      Class available since version 1.0
 */
class UsageCounterBasic implements UsageCounterInterface
{
    use TranslateableTrait;

    protected array $report = [];

    protected array $tables = [];

    protected DeleteModeEnum $confirmMode = DeleteModeEnum::Delete;

    protected ?bool $used = null;

    public function __construct(
        protected readonly ResultFetcher $resultFetcher,
        TranslatorInterface $translator,
        protected ?string $fieldName = null,
    )
    {
        $this->translate = $translator;
    }

    /**
     * @param string $tableField
     * @param string $tableName
     * @param array|string $description
     * @return void
     */
    public function addTable(string $tableField, string $tableName, mixed $description)
    {
        $this->tables["$tableName.$tableField"] = [
            'field'       => $tableField,
            'table'       => $tableName,
            'description' => $description,
            'sqlCheck'    => sprintf("SELECT %s FROM %s WHERE %s = ? LIMIT 1", $tableField, $tableName, $tableField),
            'sqlCount'    => sprintf("SELECT COUNT(%s) FROM %s WHERE %s = ? LIMIT 1", $tableField, $tableName, $tableField),
        ];
    }

    /**
     * @param string $tableField
     * @param string $tableName
     * @param string $subject1
     * @param string $subject2
     * @return void
     */
    public function addTablePlural(string $tableField, string $tableName, string $subject1, string $subject2)
    {
        $this->addTable($tableField, $tableName, [$this->plural($subject1, $subject2, 1), $this->plural($subject1, $subject2, 2)]);
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function getUsageMode(): DeleteModeEnum
    {
        return $this->confirmMode;
    }

    public function getUsageReport(): array
    {
        return array_values($this->report);
    }

    public function hasUsage($value): bool
    {
        if (null === $this->used) {
            $this->used = false;
            foreach ($this->tables as $table) {
                $output = $this->resultFetcher->fetchOne($table['sqlCheck'], [$value]);

                if ($output) {
                    $this->used = true;
                    break;
                }
            }
        }

        return $this->used;
    }

    public function setFieldName(string $fieldName): void
    {
        $this->fieldName = $fieldName;
    }

    public function setUsageMode(DeleteModeEnum $value): void
    {
        $this->confirmMode = $value;
    }

    public function setUsageReport(mixed $value): array
    {
        if (null === $this->used) {
            $this->used = false;
        }
        $this->report = [];
        foreach ($this->tables as $key => $table) {
            $count = $this->resultFetcher->fetchOne($table['sqlCount'], [$value]);

            if (is_array($table['description'])) {
                $this->report[$key] = sprintf(
                    $this->plural($this->_('%d time used in %s'), $this->_('%d times used in %s'), $count),
                    $count,
                    $this->plural(reset($table['description']), end($table['description']), $count)
                );
            } else {
                $this->report[$key] = sprintf(
                    $this->_('%d time(s) used in %s'),
                    $count,
                    $table['description']
                );
            }

            $this->tables[$key]['count'] = $count;
            if ($count > 0) {
                $this->used = true;
            }
        }

        return $this->report;
    }

}