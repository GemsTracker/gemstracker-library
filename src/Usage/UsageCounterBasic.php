<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Usage
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Usage;

use Gems\Db\ResultFetcher;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Predicate\Operator;
use Laminas\Db\Sql\Predicate\Predicate;
use Laminas\Db\Sql\Select;
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
     * @param array|string $description
     */
    public function addTable(string $tableField, string $tableName, mixed $description): self
    {
        $this->tables["$tableName.$tableField"] = [
            'field'       => $tableField,
            'table'       => $tableName,
            'description' => $description,
            'sqlCheck'    => sprintf("SELECT %s FROM %s WHERE %s = ? LIMIT 1", $tableField, $tableName, $tableField),
            'sqlCount'    => sprintf("SELECT COUNT(%s) FROM %s WHERE %s = ? LIMIT 1", $tableField, $tableName, $tableField),
        ];

        return $this;
    }

    public function addCustomQuery(Select $query, array|string $description): self
    {
        $columns = $query->getRawState(Select::COLUMNS);
        $tableField = reset($columns);
        $tableName = $query->getRawState(Select::TABLE);

        $this->tables["$tableName.$tableField"] = [
            'field'       => $tableField,
            'table'       => $tableName,
            'description' => $description,
            'sqlCheck'    => clone $query,
            'sqlCount'    => $query->columns(['count' => new Expression('COUNT(*)')]),
        ];

        return $this;
    }

    public function addTablePlural(string $tableField, string $tableName, string $subjectSingle, string $subjectPlural): self
    {
        return $this->addTable($tableField, $tableName, $this->createDescription($subjectSingle, $subjectPlural));
    }

    public function addCustomQueryPlural(Select $query, string $subjectSingle, string $subjectPlural): self
    {
        return $this->addCustomQuery($query, $this->createDescription($subjectSingle, $subjectPlural));
    }

    private function createDescription(string $subjectSingle, string $subjectPlural): array
    {
        return [
            $this->plural($subjectSingle, $subjectPlural, 1),
            $this->plural($subjectSingle, $subjectPlural, 2)
        ];
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
                $output = $this->getResults($table['sqlCheck'], $value);

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
            $count = $this->getResults($table['sqlCount'], $value);

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

    private function getResults(string|Select $query, mixed $value): int|null|string
    {
        if (is_string($query)) {
            return $this->resultFetcher->fetchOne($query, [$value]);
        }

        $requiredValuesCount = $this->processWheres($query->getRawState(Select::WHERE));

        $values = array_pad([], $requiredValuesCount, $value);

        return $this->resultFetcher->fetchOne($query, $values);
    }

    private function processWheres(Predicate $where): int
    {
        $predicates = $where->getPredicates();

        $questionMarkCount = 0;

        foreach ($predicates as $predicateArr) {
            $predicate = $predicateArr[1];

            if ($predicate instanceof Predicate) {
                $questionMarkCount += $this->processWheres($predicate);
            } elseif ($predicate instanceof Operator && $predicate->getRight() === '?') {
                $questionMarkCount++;
            }
        }

        return $questionMarkCount;
    }
}