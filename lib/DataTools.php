<?php

namespace Lib;

/* Tools for extracting / converting data, etc. */

class DataTools
{
    public static function extractDataFields($dbFields, $dataArray, $dataType, &$valueArray = [])
    {
        foreach ($dbFields as $field) {
            switch ($dataType) {
                case 'object':
                    $value = $dataArray->{$field['dataField']} ?? '';
                    break;
                case 'array':
                    $value = $dataArray[$field['dataField']] ?? '';
                    break;
                default:
                    logError("Unknown dataType '$dataType'.");

                    return false;
            }

            if (isset($field['nested'])) {
                if ($dataType === 'object' && $field['dataField'] === '@attributes') {
                    $attributes = $dataArray->attributes();
                    if (! self::extractDataFields($field['nested'], $attributes, $dataType, $valueArray)) {
                        return false;
                    }
                } elseif (! self::extractDataFields($field['nested'], $value, $dataType, $valueArray)) {
                    return false;
                }
                continue;
            }

            if (! empty($value)) { // using empty() to properly detect objects as well as strings
                if (isset($field['serialize'])) {
                    if (is_object($value)) {
                        $value = serialize(get_object_vars($value));
                    } else {
                        $value = serialize($value);
                    }
                } elseif (isset($field['commaSeparateMultiples'])) {
                    // Note: Can't just use implode(), doesn't work with these objects, must loop by element #.
                    $s = '';
                    foreach ($value as $iValue) {
                        $s .= ($s === '' ? '' : ',') . html_entity_decode((string) $iValue, ENT_QUOTES, 'UTF-8');
                    }
                    $value = $s;
                } else {
                    $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
                }
            }

            if (isset($field['isRequired']) && $field['isRequired'] === true && $value === '') {
                // (using === so that numeric values like '0' count as acceptable values)
                logWarning("[$field[dataField] is empty!]");

                return false;
            }

            if (isset($field['conversion'])) {
                if (! array_key_exists(strtolower($value), $field['conversion'])) {
                    logError("Conversion not found for $field[dataField] value '$value'.");

                    return false;
                }
                $value = $field['conversion'][strtolower($value)];
            }

            if (isset($field['isNumber']) && $field['isNumber'] === true) {
                if (isset($field['round'])) {
                    $value = (string) round($value, $field['round']);
                }
                if ($value === '') {
                    $value = '0';
                } // so we output a '0' and not '' for numeric values.
            }

            if (is_array($value)) {
                if (empty($value)) { // (if it's an empty array)
                    $value = '';
                } else {
                    logWarning("'$field[dataField]' is an array -> " . json_encode($value));

                    return false;
                }
            }

            $valueArray[$field['dbField']] = trim($value);
        }

        return $valueArray;
    }

    public static function array2xml($array)
    {
        $xml = '';
        foreach ($array as $key => $value) {
            $xml .= "<$key>";
            switch (gettype($value)) {
                case 'array':
                    $xml .= array2xml($value);
                    break;
                default:
                    $xml .= htmlspecialchars($value, ENT_XML1);
            }
            $xml .= "</$key>";
        }

        return $xml;
    }
}
