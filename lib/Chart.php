<?php

namespace Lib;

use Exception;
use Illuminate\Support\Collection;

class Chart
{
    public static $totalCharts;

    public $chartNum;

    public $title;

    public $options; // Array with possible values: 'showDatasetKeysLegend', 'disableTooltips'

    public $chartType; // 'bar', 'line', 'pie', 'doughnut'

    public $labels;

    public $datasets; // array of 'datasetKey' => 'data'

    public $borderColors;

    public $backgroundColors;

    /*
        Array of arrays with elements: 'title', 'type', 'data'
    */
    public $chart = [];

    public function __construct($chartType, $title = '', $options = [])
    {
        $this->chartNum = self::$totalCharts++;
        $this->chartType = $chartType;
        $this->title = $title;
        $this->options = $options;
    }

    public function setDefaultColors()
    {
        $this->borderColors = $this->backgroundColors = [];
        $totalColors = $this->isColoredByDataValue() ? count($this->labels) : count($this->datasets);
        for ($color = 0; $color < $totalColors; $color++) {
            $hue = ($totalColors == 1 ? 200 : round(360 * ($color / $totalColors)));
            $this->borderColors[] = "hsl($hue,30%,55%)";
            $this->backgroundColors[] = "hsl($hue,30%,80%)";
        }

        return $this;
    }

    public function isColoredByDataValue()
    {
        return in_array($this->chartType, ['pie', 'doughnut', 'polarArea']);
    }

    public function setLabelsAndData(Collection $rows, $labelField, $valueField, $separateDataByField = null, $sortLabels = false)
    {
        $this->setLabelsFromCollection($rows, $labelField, $sortLabels)
            ->setDataFromCollection($rows, $labelField, $valueField, $separateDataByField)
            ->setDefaultColors();

        return $this;
    }

    public function setLabelsFromCollection(Collection $rows, $labelField, $sort = false)
    {
        $labels = $rows->pluck($labelField)->unique();
        if ($sort) {
            $labels->sort();
        }
        $this->labels = $labels->values()->toArray();

        return $this;
    }

    public function setDataFromCollection(Collection $rows, $labelField, $valueField, $separateDataByField = null)
    {
        foreach ($rows as $row) {
            $datasetKey = ($separateDataByField !== null ? $row->$separateDataByField : 0);
            $this->setDatasetValue($datasetKey, $row->$labelField, $row->$valueField);
        }

        return $this;
    }

    public function setDatasetValue($datasetKey, $label, $value)
    {
        if (! isset($this->datasets[$datasetKey])) {
            $this->addEmptyDataset($datasetKey);
        }
        $position = array_search($label, $this->labels);
        if ($position === false) {
            throw Exception("Unknown label '$label' in data.");
        }
        $this->datasets[$datasetKey][$position] = $value;

        return $this;
    }

    public function renameLabels($nameMap, $useOriginalValueIfNotInMap = true)
    {
        $this->labels = array_map(function ($element) use ($nameMap, $useOriginalValueIfNotInMap) {
            if ($useOriginalValueIfNotInMap && ! isset($nameMap[$element])) {
                return $element;
            } else {
                return $nameMap[$element];
            }
        }, $this->labels);

        return $this;
    }

    public function renameDatasetKeys($nameMap, $useOriginalValueIfNotInMap = true)
    {
        $this->datasets = renameArrayKeys($this->datasets, $nameMap, $useOriginalValueIfNotInMap);

        return $this;
    }

    public function sortDatasetsByKey()
    {
        ksort($this->datasets);

        return $this;
    }

    private function addEmptyDataset($datasetKey)
    {
        $this->datasets[$datasetKey] = array_fill(0, count($this->labels), 0);

        return $this;
    }
}
