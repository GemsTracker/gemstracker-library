<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Repository
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Repository;

use DateTimeImmutable;
use DateTimeInterface;
use Gems\Db\ResultFetcher;
use Gems\Form\Element\DateTimeInput;
use Laminas\Db\Adapter\Platform\PlatformInterface;
use Zalt\Base\TranslateableTrait;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\Model\Type\AbstractDateType;

/**
 *
 * @package    Gems
 * @subpackage Repository
 * @since      Class available since version 1.9.2
 */
class PeriodSelectRepository
{
    use TranslateableTrait;
    
    /**
     * Field name for period filters
     */
    const PERIOD_DATE_USED = 'dateused';

    protected int $lastUsedType = MetaModelInterface::TYPE_DATE;
    
    protected PlatformInterface $platform;
    
    public function __construct(
        protected MetaModelLoader $metaModelLoader, 
        ResultFetcher $resultFetcher,
        TranslatorInterface $translate,
    )
    {
        $this->platform = $resultFetcher->getPlatform();
        $this->translate = $translate;
    }

    /**
     * Generate two date selectors and - depending on the number of $dates passed -
     * either a hidden element containing the field name or an radio button or
     * dropdown selector for the type of date to use.
     *
     * @param array $elements Search element array to which the element are added.
     * @param mixed $dates A string fieldName to use or an array of fieldName => Label
     * @param string $defaultDate Optional element, otherwise first is used.
     * @param int $switchToSelect The number of dates where this function should switch to select display
     */
    public function addZendPeriodSelectors(array &$elements, string|null $fromLabel = null)
    {
        // $config = $this->metaModelLoader->getModelConfig();
        $type = $this->lastUsedType ?? MetaModelInterface::TYPE_DATE;
        $dateType = $this->metaModelLoader->getDefaultTypeInterface($type);

        if ($fromLabel === null) {
            $fromLabel = $this->_('From');
        }

        $elements['datefrom'] = new DateTimeInput('datefrom', $dateType->getSettings() + ['label' => $fromLabel ]);

        $options['label'] = $this->_('until');
        $elements['dateuntil'] = new DateTimeInput('dateuntil', $dateType->getSettings() + $options);
    }

    /**
     * Helper function to generate a period query string
     *
     * @param array $filter A filter array or $request->getParams()
     * @param ?string $inFormat Optional format to use for date when reading
     * @param ?string $outFormat Optional format to use for date in query
     * @param array $defaults Default values to use when the current are empty
     * @return string
     */
    public function createPeriodFilter(array &$filter, ?string $inFormat = null, ?string $outFormat = null, array $defaults = [])
    {
        $from   = array_key_exists('datefrom', $filter) ? $filter['datefrom'] : null;
        $until  = array_key_exists('dateuntil', $filter) ? $filter['dateuntil'] : null;
        $period = array_key_exists(self::PERIOD_DATE_USED, $filter) ? $filter[self::PERIOD_DATE_USED] : null;

        unset($filter[self::PERIOD_DATE_USED], $filter['datefrom'], $filter['dateuntil']);

        if (! $period) {
            return;
        }

        $dateType = $this->metaModelLoader->getDefaultTypeInterface(MetaModelInterface::TYPE_DATE);
        if (null === $outFormat) {
            $outFormat = $dateType instanceof AbstractDateType ? $dateType->storageFormat : 'Y-m-d';
        }
        if (null === $inFormat) {
            $inFormat  = $dateType instanceof AbstractDateType ? $dateType->dateFormat : 'd-m-Y';
        }

        $this->lastUsedType = self::getFormatDataType($inFormat);
        if (! $this->lastUsedType) {
            $this->lastUsedType = self::getFormatDataType($outFormat);
        }

        $datefrom  = $this->dateToString($from, $inFormat, $outFormat, $defaults['datefrom'] ?? null);
        $dateuntil = $this->dateToString($until, $inFormat, $outFormat, $defaults['dateuntil'] ?? null);

        if (! ($datefrom || $dateuntil)) {
            return;
        }

        switch ($period[0]) {
            case '_':
                // overlaps
                $periods = explode(' ', substr($period, 1));

                if ($datefrom && $dateuntil) {
                    return sprintf(
                        '(%1$s <= %4$s OR (%1$s IS NULL AND %2$s IS NOT NULL)) AND
                                (%2$s >= %3$s OR %2$s IS NULL)',
                        $this->quoteIdentifier($periods[0]),
                        $this->quoteIdentifier($periods[1]),
                        $this->quoteValue($datefrom),
                        $this->quoteValue($dateuntil)
                    );
                }
                if ($datefrom) {
                    return sprintf(
                        '%2$s >= %3$s OR (%2$s IS NULL AND %1$s IS NOT NULL)',
                        $this->quoteIdentifier($periods[0]),
                        $this->quoteIdentifier($periods[1]),
                        $this->quoteValue($datefrom)
                    );
                }
                if ($dateuntil) {
                    return sprintf(
                        '%1$s <= %3$s OR (%1$s IS NULL AND %2$s IS NOT NULL)',
                        $this->quoteIdentifier($periods[0]),
                        $this->quoteIdentifier($periods[1]),
                        $this->quoteValue($dateuntil)
                    );
                }
                return;

            case '-':
                // within
                $periods = explode(' ', substr($period, 1));

                if ($datefrom && $dateuntil) {
                    return sprintf(
                        '%1$s >= %3$s AND %2$s <= %4$s',
                        $this->quoteIdentifier($periods[0]),
                        $this->quoteIdentifier($periods[1]),
                        $this->quoteValue($datefrom),
                        $this->quoteValue($dateuntil)
                    );
                }
                if ($datefrom) {
                    return sprintf(
                        '%1$s >= %3$s AND (%2$s IS NULL OR %2$s >= %3$s)',
                        $this->quoteIdentifier($periods[0]),
                        $this->quoteIdentifier($periods[1]),
                        $this->quoteValue($datefrom)
                    );
                }
                if ($dateuntil) {
                    return sprintf(
                        '%2$s <= %3$s AND (%1$s IS NULL OR %1$s <= %3$s)',
                        $this->quoteIdentifier($periods[0]),
                        $this->quoteIdentifier($periods[1]),
                        $this->quoteValue($dateuntil)
                    );
                }
                return;

            default:
                if ($datefrom && $dateuntil) {
                    return sprintf(
                        '%s BETWEEN %s AND %s',
                        $this->quoteIdentifier($period),
                        $this->quoteValue($datefrom),
                        $this->quoteValue($dateuntil)
                    );
                }
                if ($datefrom) {
                    return sprintf(
                        '%s >= %s',
                        $this->quoteIdentifier($period),
                        $this->quoteValue($datefrom)
                    );
                }
                if ($dateuntil) {
                    return sprintf(
                        '%s <= %s',
                        $this->quoteIdentifier($period),
                        $this->quoteValue($dateuntil)
                    );
                }
                return;
        }
    }

    /**
     * @param mixed $value
     * @param string $inFormat
     * @param string $outFormat
     * @param mixed $default
     * @return string|null
     */
    public function dateToString(mixed $value, string $inFormat, string $outFormat, mixed $default): ?string
    {
        if ($value === null) {
            if ($default === null) {
                return null;
            }
            $value = $default;
        }
        if ($value instanceof DateTimeInterface) {
            $date = $value;
        } else {
            $date = DateTimeImmutable::createFromFormat($inFormat, trim($value));
            if ((! $date) && $default) {
                if ($default instanceof DateTimeInterface) {
                    $date = $default;
                } else {
                    $date = DateTimeImmutable::createFromFormat($inFormat, $default);
                }
            }
        }
        if (! $date) {
            return null;
        }

        return $date->format($outFormat);
    }
    
    public static function getFormatDataType($format): int
    {
        $dateChars = 'dmYyDjlNSwzWFMntLoXxYycr';
        $timeChars = 'HisaABGGhuveIOPTZcru';

        $noDate = (false === strpbrk($format, $dateChars));
        $noTime = (false === strpbrk($format, $timeChars));
        
        if ($noTime && $noDate) {
            return 0;
        }
        if ($noTime) {
            return MetaModelInterface::TYPE_DATE;
        }
        if ($noDate) {
            return MetaModelInterface::TYPE_TIME;
        }
        return MetaModelInterface::TYPE_DATETIME;
    }
    
    public function quoteIdentifier(string $identifier): string
    {
        return $this->platform->quoteIdentifier($identifier);
    }
    
    public function quoteValue(mixed $value): string
    {
        return $this->platform->quoteValue($value);
    }
}
