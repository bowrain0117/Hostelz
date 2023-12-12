<?php

namespace Lib;

use DB;
use Request;
use View;

class DataCorrectionHandler
{
    public static function massDataCorrection($view, $dbTable, $dbField, $editURL, $contextField1 = null, $contextField2 = null, $queryBasis = null,
        $actualDbTable = null, $actualDbField = null, $skipByDefault = false)
    {
        DB::disableQueryLog(); // to save memory

        // (the dbTable/dbField we store in the dataCorrection database can be different than the actual DB table/field that is being checked / updated)
        if ($actualDbTable == null) {
            $actualDbTable = $dbTable;
        }
        if ($actualDbField == null) {
            $actualDbField = $dbField;
        }

        if (Request::has('data')) {
            set_time_limit(15 * 60);

            $inserts = [];
            $data = Request::input('data');

            foreach ($data as $row) {
                if (isset($row['skip'])) {
                    continue;
                }

                $insert = DataCorrection::saveCorrection($dbTable, $dbField, $row['old'], $row['new'], (string) ($row['context1'] ?? null), (string) ($row['context2'] ?? null), true);
                if ($insert) {
                    $inserts[] = $insert;
                } // we do all the inserts at once so it's faster

                // Update DB data
            }

            if ($inserts) {
                DataCorrection::insert($inserts);
            }

            $correctionsOutput = DataCorrection::correctAllDatabaseValues($dbTable, $dbField, $queryBasis, $actualDbTable, $actualDbField, $contextField1, $contextField2);

            return view($view, compact('inserts', 'editURL', 'dbTable', 'dbField', 'actualDbTable', 'actualDbField', 'correctionsOutput'));
        } else {
            $correctionsOutput = DataCorrection::correctAllDatabaseValues($dbTable, $dbField, $queryBasis, $actualDbTable, $actualDbField, $contextField1, $contextField2);

            $select = ["$actualDbTable.$actualDbField as value"];
            if ($contextField1 !== null) {
                $select[] = "$actualDbTable.$contextField1 as contextValue1";
            }
            if ($contextField2 !== null) {
                $select[] = "$actualDbTable.$contextField2 as contextValue2";
            }
            if (! $queryBasis) {
                $queryBasis = DB::table($actualDbTable);
            }

            // Find unknown values
            $rows = $queryBasis->select($select)->where("$actualDbTable.$actualDbField", '!=', '')
                ->leftJoin('dataCorrection', function ($join) use ($actualDbTable, $actualDbField, $contextField1, $contextField2) {
                    $join->on(DB::raw("BINARY $actualDbTable.$actualDbField"), '=', DB::raw('BINARY dataCorrection.newValue'));
                    if ($contextField1 !== null) {
                        $join->on('dataCorrection.contextValue1', '=', "$actualDbTable.$contextField1");
                    }
                    if ($contextField2 !== null) {
                        $join->on('dataCorrection.contextValue2', '=', "$actualDbTable.$contextField2");
                    }
                })
                ->whereNull('dataCorrection.id')
                ->groupBy(DB::raw("BINARY $actualDbTable.$actualDbField" . ($contextField1 != '' ? ", $actualDbTable.$contextField1" : '') . ($contextField2 != '' ? ", $actualDbTable.$contextField2" : '')))
                ->get()->all();

            // Add suggestions
            foreach ($rows as $rowKey => $row) {
                $rows[$rowKey]->suggestions = DataCorrection::findFuzzyCorrectDataMatches($dbTable, $dbField, $row->value,
                    $contextField1 != '' ? $row->contextValue1 : '', $contextField2 != '' ? $row->contextValue2 : '', 5);
            }

            // View
            return view($view, compact('rows', 'editURL', 'dbTable', 'dbField', 'actualDbTable', 'actualDbField', 'skipByDefault', 'correctionsOutput'));
        }
    }
}
