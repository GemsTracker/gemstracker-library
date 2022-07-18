<?php

namespace Gems\Event;

trait EventCalculationsTrait
{
    /**
     * Adds value to $results when it is different from the value in $tokenAnswers.
     *
     * @param string $name The name of the values
     * @param mixed $value The value to compare to
     * @param array $results The results to add to
     * @param array $tokenAnswers The answers to compare to
     * @return boolean True when the value changed.
     */
    public static function addWhenChanged($name, $value, array &$results, array $tokenAnswers)
    {
        if (array_key_exists($name, $tokenAnswers) && $value != $tokenAnswers[$name]) {
            $results[$name] = $value;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Finds those tokenAnswers array keys that contain "fieldNames" in their key.
     *
     * @param array $tokenAnswers
     * @param string $fieldNames
     * @return array
     */
    private static function _arrayFindName(array $tokenAnswers, $fieldNames)
    {
        $results = array();

        foreach ($tokenAnswers as $fieldName => $value) {
            if (strpos($fieldName, $fieldNames) !== false) {
                $results[] = $fieldName;
            }
        }

        // \MUtil_Echo::track($results);

        return $results;
    }

    /**
     * Returns the avarage over those $fieldNames values that exist in $tokenAnswers and are integers.
     *
     * @param array $tokenAnswers Array containing the answers
     * @param mixed $fieldNames An array of those names that should be used or a string that should occur in all names that have to be selected.
     * @return float
     */
    public static function averageInt(array $tokenAnswers, $fieldNames)
    {
        if (is_string($fieldNames)) {
            $fieldNames = self::_arrayFindName($tokenAnswers, $fieldNames);
        }

        $count = 0;
        $sum   = 0;
        foreach ($fieldNames as $name) {
            if (isset($tokenAnswers[$name]) && (is_int($tokenAnswers[$name]) || (string) intval($tokenAnswers[$name]) === $tokenAnswers[$name])) {
                $count++;
                $sum += intval($tokenAnswers[$name]);
            }
        }
        return $count ? $sum / $count : null;
    }

    /**
     * Checks all $values for a change against $tokenAnswers as floats
     *
     * @param array $values
     * @param array $tokenAnswers
     * @return array Those values that were changed.
     */
    public static function checkFloatChanged(array $values, array $tokenAnswers)
    {
        $results = array();

        foreach($values as $name => $result) {
            if (array_key_exists($name, $tokenAnswers) && (! ((null === $result) && (null === $tokenAnswers[$name])))) {
                $result = round(floatval($result), 13);
                if (((string) $tokenAnswers[$name] != (string) $result) || (null === $tokenAnswers[$name])) {
                    // Round to a number the LS database can hold
                    $results[$name] = $result;
                }
            }
        }

        return $results;
    }

    /**
     * Checks all $values for a change against $tokenAnswers as integer
     *
     * @param array $values
     * @param array $tokenAnswers
     * @return array Those values that were changed.
     */
    public static function checkIntegerChanged(array $values, array $tokenAnswers)
    {
        $results = array();

        foreach($values as $name => $result) {
            $result = intval($result);
            if (($tokenAnswers[$name] != $result) || ($tokenAnswers[$name] === null)) {
                $results[$name] = $result;
            }
        }

        return $results;
    }

    /**
     * Reverses the code value of an item.
     *
     * Used when a code can have the values 1 to 5 or 0 to 9 and the reverse
     * outcome should be used, e.g.: 5 = 1, 4 = 2, 3 = 3, 2 = 4, 1 = 5.
     *
     * @param int $code
     * @param int $min
     * @param int $max
     * @return int
     */
    public static function reverseCode($code, $min, $max)
    {
        return $max - ($code - $min);
    }


    /**
     * Rounds the value with a fixed number of decimals, padding
     * zeros when required.
     *
     * @param numeric $value
     * @param int $decimals
     * @return string
     */
    public static function roundFixed($value, $decimals = 2)
    {
        $value = round($value, $decimals);

        $pos = strpos($value, '.');

        if ($pos === false) {
            $value .= '.' . str_repeat('0', $decimals);
        } else {
            $extra = $decimals - strlen($value) + $pos + 1;
            if ($extra > 0) {
                $value .= str_repeat('0', $extra);
            }
        }

        return $value;
    }

    /**
     * Returns the sum over those $fieldNames values that exist in $tokenAnswers and are integers
     * @param array $tokenAnswers Array containing the answers
     * @param mixed $fieldNames An array of those names that should be used or a string that should occur in all names that have to be selected.
     * @return int
     */
    public static function sumInt(array $tokenAnswers, $fieldNames)
    {
        if (is_string($fieldNames)) {
            $fieldNames = self::_arrayFindName($tokenAnswers, $fieldNames);
        }

        $sum = 0;
        foreach ($fieldNames as $name) {
            if (isset($tokenAnswers[$name]) && (is_int($tokenAnswers[$name]) || (string) intval($tokenAnswers[$name]) === $tokenAnswers[$name])) {
                $sum += intval($tokenAnswers[$name]);
            }
        }
        return $sum;
    }
}