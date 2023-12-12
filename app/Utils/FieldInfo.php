<?php

namespace App\Utils;

class FieldInfo
{
    public static function fieldInfoType(array &$fieldInfos, array $staffEditable, array $staffIgnore): void
    {
        foreach ($fieldInfos as $fieldName => $fieldInfo) {
            if ((isset($fieldInfo['type']) && $fieldInfo['type'] !== 'ignore') &&
                (isset($fieldInfo['editType']) && $fieldInfo['editType'] !== 'ignore') &&
                ! in_array($fieldName, $staffEditable, true)) {
                $fieldInfos[$fieldName]['editType'] = 'display';
            }
            if (in_array($fieldName, $staffIgnore, true)) {
                $fieldInfos[$fieldName]['type'] = $fieldInfos[$fieldName]['searchType'] = $fieldInfos[$fieldName]['editType'] = 'ignore';
            }
        }
    }
}
