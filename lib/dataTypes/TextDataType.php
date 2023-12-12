<?php

namespace Lib\dataTypes;

use DB;
use Exception;
use Schema;

/*
    This stores text data is a special separate textData table.  Useful for keeping the main table small.
    Currently not searchable.
*/

class TextDataType extends DataType
{
    public function createStorage()
    {
        if (Schema::hasTable('textData')) {
            return;
        }

        Schema::create('textData', function ($table) {
            $table->increments('id');
            $table->string('tableName');
            $table->string('fieldName');
            $table->integer('objectID')->unsigned()->index();
            $table->text('textData');
        });
    }

    public function removeStorage()
    {
        if (Schema::hasTable('textData')) {
            Schema::drop('textData');
        }
    }

    public function getValue($model)
    {
        if (! $model->id) {
            return '';
        } // model isn't yet saved, so it can't yet have data associated with it in the database.
        debugOutput('get() from DB.');
        $row = $this->getRow($model->id);

        return $row ? $row->textData : '';
    }

    /* Events */

    public function saving($model, $value)
    {
        // No nothing.
    }

    public function saved($model, $value)
    {
        debugOutput("set('$value')");

        if ($value == '') {
            return $this->delete($model);
        } // if it's '', we just delete it.

        if (! $model->id) {
            throw new Exception('model ID not set.');
        } // shouldn't happen.

        $existing = $this->getRow($model->id);
        if (! $existing) {
            // Row doesn't yet exist, insert it.
            DB::table('textData')->insert([
                'tableName' => $this->tableName, 'fieldName' => $this->fieldName, 'objectID' => $model->id, 'textData' => $value,
            ]);
        } else {
            // Row already exists, just update it.
            DB::table('textData')->where('id', $existing->id)->update(['textData' => $value]);
        }
    }

    public function delete($model)
    {
        debugOutput('Delete.');
        DB::table('textData')->where('tableName', $this->tableName)->where('fieldName', $this->fieldName)->where('objectID', $model->id)->delete();
    }

    /* Queries */

    public function getDefaultComparisonType($inputType, $value, $isPrimaryKey)
    {
        return 'substring';
    }

    public function searchQuery($query, $values, $comparisonType, $specialSearch = false)
    {
        throw new Exception('searchQuery not yet implemented.');
    }

    /* Private Functions */

    private function getRow($objectID)
    {
        return DB::table('textData')->where('tableName', $this->tableName)->where('fieldName', $this->fieldName)
            ->where('objectID', $objectID)->first();
    }
}
