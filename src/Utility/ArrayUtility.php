<?php

namespace SourceBroker\DeployerExtendedDatabase\Utility;

/**
 * Class ArrayUtility
 * @package SourceBroker\DeployerExtendedDatabase\Utility
 */
class ArrayUtility
{
    /**
     * @param array $array1
     * @param array $array2
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
            foreach ($append as $key => $value) {
                if (!array_key_exists($key, $base) && !is_numeric($key)) {
                    $base[$key] = $append[$key];
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
        }
        return $base;
    }


    /**
     * Filter $haystack array items with items from array $patterns.
     * Example usage:
     * filterWithRegexp(['cf_.*', 'bcd'], ['abc', 'cf_test1', 'bcd' ,'cf_test2', 'cde']) will return ['cf_test1', 'bcd', 'cf_test2']
     *
     * @param array $patterns
     * @param array $haystack
     * @return array
     */
    public function filterWithRegexp(array $patterns, array $haystack)
    {
        $foundItems = [];
        foreach ($patterns as $pattern) {
            $regexp = false;

            set_error_handler(function () {
            }, E_WARNING);
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
