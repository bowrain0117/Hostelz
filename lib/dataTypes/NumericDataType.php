<?php

namespace Lib\dataTypes;

use Exception;

class NumericDataType extends DataType
{
    public function getDefaultComparisonType($inputType, $value, $isPrimaryKey)
    {
        if (is_array($value)) {
            if ($inputType == 'minMax') {
                return 'minMax';
            } else {
                return 'matchAny';
            }
        } else {
            return 'equals';
        }
    }

    /* Queries */

    public function searchQuery($query, $value, $comparisonType, $specialSearch = false)
    {
        switch ($comparisonType) {
            /* Note: Can also instead search by [min] or [max] with searchType 'minMax'. */

            case 'greaterThan':
                $query->where($this->getFullFieldName(), '>', $value);
                break;

            case 'greaterThanEquals':
                $query->where($this->getFullFieldName(), '>=', $value);
                break;

            case 'lessThan':
                $query->where($this->getFullFieldName(), '<', $value);
                break;

            case 'lessThanEquals':
                $query->where($this->getFullFieldName(), '<=', $value);
                break;

            default:
                // Some other comparison (such as minMax or matchAny), let the parent DataType class handle it.
                return parent::searchQuery($query, $value, $comparisonType, $specialSearch);
        }

        $this->addLeftJoinIfNeeded($query);

        return $query;
    }
}
