<?php

namespace Lib;

use Redirect;
use Request;

class FileListHandler
{
    public $list;

    public $langFile = 'FileList'; // used by fileList.blade.php for column names

    public $listFields;

    public $editableFields;

    public $allowDelete = false;

    public $useIsPrimary = false; // Display/set the "isPrimary" field (typically of photos)

    public $makeSortableUsingNumberField = false; // Allow the user to re-order the items, which are numbered using this database field

    public $viewLinkClosure;

    public $retainFilenameExtensionForField = 'filename'; // special handling for filenames, don't let them change the extension.

    public $filesModified = false;

    public $picListSizeTypeNames = null;

    public $showThumb = false;

    // $editableFields = true to allow all $listFields to be edited.

    public function __construct($list, $listFields = null, $editableFields = null, $allowDelete = false, $showThumb = false)
    {
        $this->list = $list;
        $this->listFields = $listFields;
        $this->editableFields = ($editableFields === true ? $listFields : $editableFields);
        $this->allowDelete = $allowDelete;
        $this->showThumb = $showThumb;
    }

    public function go()
    {
        switch (Request::input('fileListCommand')) {
            case 'delete':
                if (! $this->allowDelete) {
                    return null;
                }
                $itemID = Request::input('item');
                $item = $this->list->where('id', $itemID)->first();
                if ($item) {
                    $wasPrimary = ($this->useIsPrimary && $item->isPrimary);
                    $item->delete();

                    // Remove the item from the collection so that the collection also (not sure if this is the best way to do this)
                    foreach ($this->list as $key => $item) {
                        if ($item->id == $itemID) {
                            $this->list->forget($key);
                            break;
                        }
                    }

                    if ($wasPrimary && ! $this->list->isEmpty()) {
                        // Make the first remaining item the new primary one
                        $firstItem = $this->list->first();
                        $firstItem->isPrimary = true;
                        $firstItem->save();
                    }
                    $this->filesModified = true;
                }

                return Redirect::to(Request::url()); // refresh the page

            case 'makePrimary':
                if (! $this->useIsPrimary) {
                    return null;
                }
                $itemID = (int) (Request::input('item'));
                $item = $this->list->find($itemID);
                if ($item) {
                    $item->isPrimary = true;
                    $item->save();

                    // Unset isPrimary for any other primary items in the list...
                    foreach ($this->list as $item) {
                        if ($item->id !== $itemID && $item->isPrimary) {
                            $item->isPrimary = false;
                            $item->save();
                        }
                    }
                    $this->filesModified = true;
                }

                return Redirect::to(Request::url()); // refresh the page

            case 'editValue':
                $field = Request::input('field');
                $value = Request::input('value');
                $itemID = Request::input('item');

                debugOutput("itemID:$itemID, field:$field, value:$value");

                if ($field == '' || ! in_array($field, $this->editableFields)) {
                    logWarning("'$field' not in editableFields.");

                    return 'failed';
                }

                $item = $this->list->find($itemID);
                if (! $item) {
                    logWarning("Item '$itemID' not found in the list.");

                    return 'failed';
                }

                // Special handling for filenames
                if ($field == $this->retainFilenameExtensionForField) {
                    $value = str_replace('/', '-', $value); // replace any slashes with dashes
                    $originalExtension = pathinfo($item->$field, PATHINFO_EXTENSION);
                    $newPathInfo = pathinfo($value);
                    if ($originalExtension != '' && (! array_key_exists('extension', $newPathInfo) || strcasecmp($newPathInfo['extension'], $originalExtension) != 0)) {
                        // change it back to the original extension
                        $value = $newPathInfo['filename'] . '.' . $originalExtension;
                    }
                }

                $item->$field = $value;
                $item->save();
                $this->filesModified = true;

                return 'ok'; // tells the Javascript that it was updated successfully.

            case 'reorder':
                if (! $this->makeSortableUsingNumberField) {
                    return 'failed';
                }
                $newOrder = Request::input('sortOrder'); // an array of the IDs of the items in the new order
                if (! is_array($newOrder)) {
                    return 'failed';
                }
                foreach ($newOrder as $positionNum => $itemID) {
                    $item = $this->list->find($itemID);
                    if (! $item) {
                        return 'failed';
                    }
                    $fieldName = $this->makeSortableUsingNumberField;
                    $item->$fieldName = $positionNum;
                    $item->save();
                }
                $this->filesModified = true;

                return 'ok';
        }
    }
}
