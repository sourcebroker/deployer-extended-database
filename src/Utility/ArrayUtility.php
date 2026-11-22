<?php

namespace SourceBroker\DeployerExtendedDatabase\Utility;

class ArrayUtility
{
    /**
     * Special key "__UNSET" in $array2 removes listed values from the list it is in, e.g.
     * ['ignore_tables_out' => ['__UNSET' => ['sys_refindex']]]. Values are removed after merging,
     * so values added in the same $array2 are removed too. The "__UNSET" key is never returned.
     *
     * @return array|mixed
     */
    public function arrayMergeRecursiveDistinct(array &$array1, array &$array2)
    {
        $arrays = [$array1, $array2];
        $base = array_shift($arrays);
        if (!is_array($base)) {
            $base = empty($base) ? [] : [$base];
        }
        foreach ($arrays as $append) {
            if (!is_array($append)) {
                $append = [$append];
            }
            $unsetValues = null;
            if (array_key_exists('__UNSET', $append)) {
                $unsetValues = (array)$append['__UNSET'];
                unset($append['__UNSET']);
            }
            foreach ($append as $key => $value) {
                if (!array_key_exists($key, $base) && !is_numeric($key)) {
                    $base[$key] = is_array($value) ? self::removeUnsetKeys($value) : $value;
                    continue;
                }
                if (is_array($value) || is_array($base[$key])) {
                    $base[$key] = self::arrayMergeRecursiveDistinct($base[$key], $append[$key]);
                } else {
                    if (is_numeric($key)) {
                        if (!in_array($value, $base)) {
                            $base[] = $value;
                        }
                    } else {
                        $base[$key] = $value;
                    }
                }
            }
            if ($unsetValues !== null) {
                $base = self::removeValues($base, $unsetValues);
            }
        }
        return $base;
    }

    /**
     * Remove values from list items (integer keys) and reindex the list.
     */
    private static function removeValues(array $array, array $values): array
    {
        foreach ($array as $key => $value) {
            if (is_int($key) && in_array($value, $values, true)) {
                unset($array[$key]);
            }
        }
        foreach (array_keys($array) as $key) {
            if (!is_int($key)) {
                return $array;
            }
        }
        return array_values($array);
    }

    /**
     * Remove "__UNSET" keys (on any level) from array which is added without merging.
     */
    private static function removeUnsetKeys(array $array): array
    {
        if (!self::containsUnsetKey($array)) {
            return $array;
        }
        $unsetValues = null;
        if (array_key_exists('__UNSET', $array)) {
            $unsetValues = (array)$array['__UNSET'];
            unset($array['__UNSET']);
        }
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = self::removeUnsetKeys($value);
            }
        }
        if ($unsetValues !== null) {
            $array = self::removeValues($array, $unsetValues);
        }
        return $array;
    }

    private static function containsUnsetKey(array $array): bool
    {
        foreach ($array as $key => $value) {
            if ($key === '__UNSET' || (is_array($value) && self::containsUnsetKey($value))) {
                return true;
            }
        }
        return false;
    }


    /**
     * Filter $haystack array items with items from array $patterns.
     * Example usage:
     * filterWithRegexp(['cf_.*', 'bcd'], ['abc', 'cf_test1', 'bcd' ,'cf_test2', 'cde']) will return ['cf_test1', 'bcd', 'cf_test2']
     */
    public function filterWithRegexp(array $patterns, array $haystack): array
    {
        $foundItems = [];
        foreach ($patterns as $pattern) {
            $regexp = false;

            set_error_handler(function () {}, E_WARNING);
            $isValidPattern = preg_match($pattern, '') !== false;
            $isValidPatternDelimiters = preg_match('/^' . $pattern . '$/', '') !== false;
            restore_error_handler();

            if (preg_match('/^[\/\#\+\%\~]/', $pattern) && $isValidPattern) {
                $regexp = $pattern;
            } elseif ($isValidPatternDelimiters) {
                $regexp = '/^' . $pattern . '$/i';
            }
            if ($regexp) {
                $foundItems = array_merge($foundItems, preg_grep($regexp, $haystack));
            } elseif (in_array($pattern, $haystack)) {
                $foundItems[] = $pattern;
            }
        }
        return $foundItems;
    }
}
