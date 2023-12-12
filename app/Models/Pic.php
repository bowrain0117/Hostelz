<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Lib\BaseModel;
use Lib\ImageProcessor;
use Lib\ImageSearch;

class Pic extends BaseModel
{
    use HasFactory;

    protected $table = 'pics';

    public static $staticTable = 'pics'; // just here so we can get the table name without needing an instance of the object

    protected $guarded = [];

    public $timestamps = false; // we'll manage our own timestamps

    protected $casts = [
        'storageTypes' => 'array', // (stores it as json)
    ];

    public const CLOUD_STORAGE_DISK = 'spaces1';

    public const PUBLIC_STORAGE_DISK = 'public';

    public const FOLDER = 'pics';

    public const GALLERY_PREVIEW = 6;

    public static $statusOptions = ['new', 'denied', 'markedForEditing', 'ok'];

    public $defaultFileExtension = 'jpg';

    public function delete(): void
    {
        $this->deletePicFiles();
        parent::delete();
    }

    /* Static */

    public static function makeFromFilePath($filepath, array $picAttributes, array $sizeTypes)
    {
        $image = ImageProcessor::makeFromFile($filepath);
        if (! $image) {
            return null;
        }

        $pic = new static($picAttributes);
        $pic->setAttributesFromImage($image);
        $pic->save();
        $pic->saveImageFiles($image, $sizeTypes);

        return $pic;
    }

    public static function fieldInfo($purpose = null)
    {
        switch ($purpose) {
            case null:
            case 'adminEdit':
            case 'staffEdit':
                $fieldInfos = [
                    'id' => ['isPrimaryKey' => true, 'editType' => 'display'],
                    'status' => ['type' => 'select', 'options' => self::$statusOptions, 'optionsDisplay' => 'translate'],
                    'subjectID' => ['dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int'],
                    'subjectType' => ['maxLength' => 100, 'comparisonType' => 'equals'],
                    'type' => ['maxLength' => 100, 'comparisonType' => 'equals'],
                    'source' => ['maxLength' => 100, 'comparisonType' => 'equals'],
                    'picNum' => ['dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int'],
                    'featuredPhoto' => ['type' => 'checkbox', 'value' => true, 'checkboxText' => ' '],
                    'isPrimary' => ['type' => 'checkbox', 'value' => true, 'checkboxText' => ' '],
                    'caption' => ['maxLength' => 250],
                    'originalFiletype' => ['type' => 'display', 'searchType' => 'text'],
                    'originalWidth' => ['type' => 'display', 'searchType' => 'minMax'],
                    'originalHeight' => ['type' => 'display', 'searchType' => 'minMax'],
                    'originalAspect' => ['type' => 'display', 'searchType' => 'minMax'],
                    'originalFilesize' => ['type' => 'display', 'searchType' => 'minMax'],
                    'originalMD5hash' => ['type' => 'display'],
                    'imageSearchCheckDate' => ['type' => 'datePicker', 'dataType' => 'Lib\dataTypes\NullableDateDataType', 'dataAccessMethod' => 'dataType', 'maxLength' => 80],
                    'imageSearchMatches' => ['type' => 'textarea', 'rows' => 4],
                    'edits' => ['type' => 'display', 'getValue' => function ($formHandler, $model) {
                        return $model->attributes['edits']; // just output the json encoded string
                    }],
                    'storageTypes' => ['type' => 'display', 'searchType' => 'text', 'getValue' => function ($formHandler, $model) {
                        return $model->attributes['storageTypes']; // just output the json encoded string
                    }],
                    'lastUpdate' => ['type' => 'display', 'searchType' => 'datePicker',
                        'dataType' => 'Lib\dataTypes\NullableDateDataType', 'dataAccessMethod' => 'dataType', ],
                ];

                break;

            default:
                throw new Exception("Unknown purpose '$purpose'.");
        }

        return $fieldInfos;
    }

    public static function make100DirsLocal($path): void
    {
        $picsPath = $GLOBALS['PUBLIC_PATH_DYNAMIC_DATA'] . '/' . self::FOLDER;
        for ($i = 0; $i <= 99; $i++) {
            if (File::missing("$picsPath/$path/$i")) {
                File::makeDirectory("$picsPath/$path/$i", 0770, true, true);
            }
        }
    }

    public static function make100DirsCloud($subjectType, $type, $sizeTypes): void
    {
        self::makeCloudDirectory(self::FOLDER);

        $subFolder = self::getSubFolder($subjectType);

        $path = self::FOLDER . '/' . $subFolder;
        self::makeCloudDirectory($path);
        if ($type !== '') {
            $path .= '/' . $type;
            self::makeCloudDirectory($path);
        }

        foreach ($sizeTypes as $sizeType) {
            self::makeCloudDirectory($path . '/' . $sizeType);
            for ($i = 0; $i <= 99; $i++) {
                self::makeCloudDirectory($path . '/' . $sizeType . '/' . $i);
            }
        }
    }

    public static function makeCloudDirectory(string $path): bool
    {
        if (Storage::disk(self::CLOUD_STORAGE_DISK)->exists($path)) {
            return true;
        }

        return Storage::disk(self::CLOUD_STORAGE_DISK)->makeDirectory($path);
    }

    public static function maintenanceTasks($timePeriod)
    {
        $output = '';

        switch ($timePeriod) {
            case 'weekly':
                $output .= "\nOptimimize table.\n";
                DB::statement('OPTIMIZE TABLE ' . self::$staticTable);

                /*
                // (don't know if these are still an issue)

            	taskTitle("Delete Bad Pics");

            	$badOriginalPics = array(
            		'dffdab1da14e5b6b100a2c240867ccf2', // 'picture coming soon'
            		'9af59faabf2cb9644086df5244ae13a9',
            		'b632070290b4e500f60101ff16d6e92a',
            		'835e2bc00c1a35b2c59913bd2eaf0bde', // 'best deal'
            		'558cbba2ab08a13bf88ed941976271b3', // gomio logo
            		'325472601571f31e1bf00674c368d335', // black
            	);

            	$ids = dbGetCol("SELECT id FROM pics WHERE
            		originalMD5hash IN (".implode(',',array_map('dbQuote',$badOriginalPics)).")");
            	foreach($ids as $id) {
            		Pics::deletePic($id); doQuery('');
            	}
    	        */
                break;

            default:
                throw new Exception("Unknown time period '$timePeriod'.");
        }

        return $output;
    }

    /* Note: Pass $reorder = false if using owner pics so that it will use their chosen sorting order. */

    public static function createLayout($pics, $totalWidth, $margin, $picsPerRow = 'auto', $reorder = true, $maxRows = null)
    {
        if (! $pics) {
            return false;
        }

        $pics = $pics->all();

        // * Re-order Pics *

        if ($reorder) {
            usort($pics, function ($a, $b) {
                $scorePic = function ($pic) {
                    $minGoodAspectRatio = 1.2;
                    $originalFilesize = $pic->originalFiletype == 'jpg' ? $pic->originalFilesize : $pic->originalFilesize / 10; // if non-jpeg divide filesize by factor of 10 to compensate
                    $minGoodFilesize = 50000; // anything this big or bigger we consider to be high quality
                    $score =
                        ($pic->originalAspect >= $minGoodAspectRatio ? $minGoodFilesize + $pic->originalAspect : 0) + // By adding $minGoodFilesize, the aspect ratio takes precidence if it's under $minGoodAspectRatio, othersize it has just minor influence.
                        ($originalFilesize >= $minGoodFilesize ? $minGoodFilesize : $originalFilesize); // score based on filesize only if filesize is fairly small

                    // echo "($pic[id].$pic[originalFiletype], aspect:$pic[originalAspect], size:$pic[originalFilesize]->$originalFilesize = $score)<br>";
                    return $score;
                };
                $scoreA = $scorePic($a);
                $scoreB = $scorePic($b);
                if ($scoreA != $scoreB) {
                    return $scoreA > $scoreB ? -1 : 1;
                } elseif ($a->picNum != $b->picNum) {
                    return $a->picNum > $b->picNum ? 1 : -1;
                } else {
                    return $a->id > $b->id ? 1 : -1;
                }
            });
        }

        // * Determine Pics Per Each Row *

        $picRows = [];
        $additionalPics = [];
        $picNum = 0;
        $remainingPics = count($pics);
        while ($remainingPics) {
            $picsThisRow = 0;

            if ($picsPerRow != 'auto') {
                $picsThisRow = $picsPerRow;
            } elseif (! $picRows) { // Is first row...
                $pic = reset($pics);
                $picsThisRow = ($pic->originalWidth > 450 && $pic->originalAspect >= 1.6 ? 1 : 2); // show 1 if used only if is hi-res and is wide and not tall
            } else {
                $picsThisRow = (count($pics) > 5 && count($picRows) >= 2 ? 3 : 2);
            }

            if ($remainingPics - $picsThisRow == 1) {
                $picsThisRow++;
            } // If there's only one left, just add it to the last row

            // Set this row to $picsThisRow
            if ($picsThisRow > $remainingPics) {
                $picsThisRow = $remainingPics;
            }
            $picRows[] = array_slice($pics, $picNum, $picsThisRow);
            $picNum += $picsThisRow;
            $remainingPics -= $picsThisRow;

            if ($maxRows && count($picRows) == $maxRows) {
                $additionalPics = array_slice($pics, $picNum);

                break;
            }
        }

        // * Calculate Each Pic Size *
        $result = [];
        foreach ($picRows as $rowPics) {
            $picWidth = round(($totalWidth - ($margin * (count($rowPics) - 1))) / count($rowPics));

            // Find shortest height
            $shortestHeight = 0;
            foreach ($rowPics as $k => $pic) {
                if ($pic->originalAspect == 0) {
                    logError("Missing originalAspect for $pic->id.");

                    continue 2;
                }
                $rowPics[$k]->height = round($picWidth / $pic->originalAspect);
                if ($rowPics[$k]->height < $shortestHeight || ! $shortestHeight) {
                    $shortestHeight = $rowPics[$k]->height;
                }
            }

            // echo "Pic Row (totalWidth:$totalWidth, picWidth:$picWidth, shortestHeight:$shortestHeight)<br>\n";

            // Set pic size info
            $row = [];
            foreach ($rowPics as $pic) {
                $topClip = round(($pic->height - $shortestHeight) / 2);
                $row[] = [
                    'height' => $shortestHeight + $topClip,
                    // Note: $pic->height was set above based on the originalAspect.
                    'topClip' => $topClip,
                    'marginRight' => (count($row) + 1 != count($rowPics) ? $margin : 0),
                    'pic' => $pic,
                ];
            }

            $result[] = $row;
        }

        if ($additionalPics) {
            $lastRow = end($result);
            $lastPic = end($lastRow);
            $result[key($result)][key($lastRow)]['additionalPics'] = $additionalPics;
        }

        return $result;
    }

    // This gets the pics for multiple subjectIDs (of the same subjectType) and returns them in an Collection keyed by the subjectID, each having a Collection of Pics.
    // (Used for things like getting all of the imported pics for a listing using just one database query)

    public static function getForMultipleSubjectIDs($subjectIDs, $subjectType)
    {
        return self::where('subjectType', $subjectType)
            ->whereIn('subjectID', $subjectIDs)
            ->orderBy('picNum')
            ->get()
            ->groupBy('subjectID');
    }

    /* Accessors & Mutators */

    public function getEditsAttribute($value)
    {
        return $value == '' ? [] : json_decode($value, true);
    }

    public function setEditsAttribute($value): void
    {
        $this->attributes['edits'] = ($value ? json_encode($value) : '');
    }

    /* Misc */

    public function updateImageSearchMatches($sizeTypes, $reportMatchesAsError = false)
    {
        $searchResults = ImageSearch::searchByURL($this->url($sizeTypes, 'absolute'));
        $this->imageSearchMatches = $searchResults ? implode("\n", $searchResults) : '';
        $this->imageSearchCheckDate = date('Y-m-d');

        if ($reportMatchesAsError && $this->imageSearchMatches != '') {
            logError("Pic $this->id has image search matches $this->imageSearchMatches.");
        }

        return $this; // for chaining
    }

    public function setAttributesFromImage(ImageProcessor $image): void
    {
        $this->originalFiletype = $image->originalFiletype;
        $dimensions = $image->getImageDimensions();
        $this->originalWidth = $dimensions['width'];
        $this->originalHeight = $dimensions['height'];
        $this->originalAspect = round($this->originalWidth / $this->originalHeight, 2);
        $this->originalFilesize = $image->getImageDataSizeInBytes();
        $this->originalMD5hash = md5($image->originalImageData);
    }

    public function subdirectoryAndFilename($sizeTypeName = '', $fileExtension = null)
    {
        if (strpos($sizeTypeName, 'webp') === 0) {
            $fileExtension = 'webp';
        } elseif ($fileExtension === null) {
            $fileExtension = ($sizeTypeName == 'originals' ? $this->originalFiletype : $this->defaultFileExtension);
        }

        $subFolder = self::getSubFolder($this->subjectType);

        return self::FOLDER . '/' .
            $subFolder . '/' .
            ($this->type != '' ? $this->type . '/' : '') .
            ($sizeTypeName != '' ? $sizeTypeName . '/' : '') .
            ($this->id % 100) . '/' . $this->id .
            ($fileExtension != '' ? '.' . $fileExtension : '');
    }

    public function url($sizeTypes, $urlType = 'relative')
    {
        if (! $this->id || $this->subjectType == '') {
            throw new Exception('Invalid pic.');
        }

        if (! is_array($sizeTypes)) {
            $sizeTypes = [$sizeTypes];
        }

        // There can be multiple $sizeTypes, we return the first one that exists
        foreach ($sizeTypes as $sizeType) {
            // If this sizeType isn't stored locally for this pic, try the next type
            if (empty($this->storageTypes[$sizeType])) {
                continue;
            }
            switch ($this->storageTypes[$sizeType]) {
                case 'local':
                    return rtrim(routeURL('home', [], $urlType, 'en'), '/') . '/' . // (just to get the base URL for $urlType)
                        $this->subdirectoryAndFilename($sizeType);

                case 'privateCloud':
                    // Return the path that can stream the pic data from the cloud
                    // (We don't check to see if they have permission to view it here,
                    // the streaming controller method will check that.)
                    return routeURL('cloudStreamedPic', [$this->id, $sizeType], $urlType, 'en');
            }
        }

        logWarning("Pic $this->id doesn't have sizes: " . implode(', ', $sizeTypes));

        return routeURL('images', 'noImage.png');
    }

    public function localFilePath($sizeTypeName = '', $fileExtension = null): string
    {
        return $GLOBALS['PUBLIC_PATH_DYNAMIC_DATA'] . '/' . $this->subdirectoryAndFilename($sizeTypeName, $fileExtension);
    }

    public function isExistsFileForSizeType($sizeTypeName): bool
    {
        if (empty($this->storageTypes[$sizeTypeName])) {
            return false;
        }

        $disk = $sizeTypeName === 'privateCloud' ? self::CLOUD_STORAGE_DISK : self::PUBLIC_STORAGE_DISK;

        return Storage::disk($disk)->exists($this->subdirectoryAndFilename($sizeTypeName));
    }

    public function getImageData($sizeTypeName)
    {
        if (empty($this->storageTypes[$sizeTypeName])) {
            return null;
        }

        switch ($this->storageTypes[$sizeTypeName]) {
            case 'local':
                return file_get_contents($this->localFilePath($sizeTypeName));

            case 'privateCloud':
                return Storage::disk(self::CLOUD_STORAGE_DISK)->get($this->subdirectoryAndFilename($sizeTypeName));
        }
    }

    /*
        $sizeTypes - Array of sizeTypeName => edits.
            Edits can be the edits listed in ImageProcssor::applyEdits(),
            plus 'saveAsFormat' (optional, defaults to originalFileType), 'outputQuality' (optional), 'skipIfUnmodified' (optional).
    */

    public function saveImageFiles(ImageProcessor $originalImage, array $sizeTypes, $saveOriginalDataIfUnmodified = true): void
    {
        if (! $this->id) {
            throw new \RuntimeException('Must save pic to database first.');
        }

        $storageTypes = $this->storageTypes ?: [];

        foreach ($sizeTypes as $sizeTypeName => $edits) {
            $outputFormat = $edits['saveAsFormat'] ?? $this->originalFiletype;

            $image = clone $originalImage;
            $imageModified = $image->applyEdits($edits);

            $saveOriginalData = ($saveOriginalDataIfUnmodified && $image->originalImageData != '' && ! $imageModified && $image->originalFiletype == $outputFormat);
            if ($saveOriginalData && isset($edits['skipIfUnmodified'])) {
                continue;
            }

            switch ($edits['storageType'] ?? null) {
                case 'privateCloud':
                    $result = Storage::disk(self::CLOUD_STORAGE_DISK)
                        ->put(
                            $this->subdirectoryAndFilename($sizeTypeName, $outputFormat),
                            $saveOriginalData ? $originalImage->originalImageData : $image->getImageData(),
                            'private'
                        );
                    if (! $result) {
                        throw new Exception("Couldn't save $sizeTypeName pic to cloud.");
                    }
                    $storageTypes[$sizeTypeName] = 'privateCloud';

                    break;

                case 'local':
                default:
                    $outputFilename = $this->localFilePath($sizeTypeName, $outputFormat);
                    if ($saveOriginalData) {
                        $image->saveOriginalImageData($outputFilename); // avoids re-compressing the image when possible
                    } else {
                        $image->saveAs($outputFilename, $outputFormat, $edits['outputQuality'] ?? null);
                    }
                    $storageTypes[$sizeTypeName] = 'local';

                    break;
            }
        }

        $this->storageTypes = $storageTypes;
        $this->save();
    }

    public function changeStorageType($sizeType, $newStorageType): void
    {
        $oldStorageType = $this->storageTypes[$sizeType];
        if ($oldStorageType === $newStorageType) {
            throw new Exception("Storage type was already \"$newStorageType\".");
        }

        $imageData = $this->getImageData($sizeType);
        if (! $imageData) {
            throw new Exception('No image data.');
        }

        // Delete from the old storage location
        $this->deletePicFile($sizeType);

        // Update the storageTypes array (have to do this after deleting the old type, but before saving the new type so it gets the new file path)
        $storageTypes = $this->storageTypes;
        $storageTypes[$sizeType] = $newStorageType;
        $this->storageTypes = $storageTypes;
        $this->save();

        switch ($newStorageType) {
            case 'privateCloud':
                Storage::disk(self::CLOUD_STORAGE_DISK)->put($this->subdirectoryAndFilename($sizeType), $imageData);

                break;

            case 'local':
                file_put_contents($this->localFilePath($sizeType), $imageData);

                break;

            default:
                throw new Exception("Unknown storage type \"$newStorageType\".");
        }
    }

    public function hasSizeType($sizeType)
    {
        return $this->storageTypes && array_key_exists($sizeType, $this->storageTypes);
    }

    public function deletePicFile($sizeType): void
    {
        switch ($this->storageTypes[$sizeType]) {
            case 'privateCloud':
                Storage::disk(self::CLOUD_STORAGE_DISK)->delete($this->subdirectoryAndFilename($sizeType));
                // echo "[cloud delete ".$this->subdirectoryAndFilename($sizeType)."] ";
                break;

            case 'local':
                File::delete($this->localFilePath($sizeType));
                // echo "[file delete ".$this->localFilePath($sizeType)."] ";
                break;

            default:
                throw new Exception("Unknown storage type \"$storageType\".");
        }
    }

    public function deletePicFiles(): void
    {
        if (! $this->storageTypes) {
            return;
        } // no image data was stored yet

        foreach ($this->storageTypes as $sizeType => $storageType) {
            $this->deletePicFile($sizeType);
        }

        $this->storageTypes = [];
        // Note: We don't bother to save the updated pic record because usually we're about the delete it anyway when we call this.
    }

    private static function getSubFolder($subjectType)
    {
        return $subjectType === 'ads' ? 'aside' : $subjectType;
    }

    public static function getDefaultImageUrl()
    {
        return routeURL('images', 'noImage.jpg', 'absolute');
    }
}
