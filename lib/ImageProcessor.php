<?php

namespace Lib;

use Exception;
use Imagick;
use ImagickPixel;

class ImageProcessor
{
    const BROWSER_SUPPORTED_FORMATS = ['jpg', 'png', 'gif'];

    public const MEMORY_LIMIT = '512M';

    public $originalImageData; // optional

    public $originalFiletype; // optional

    public $defaultOutputQuality = 75;

    /* @var Imagick $imagick */
    private $imagick;

    public function __clone()
    {
        if ($this->imagick) {
            $this->imagick = clone $this->imagick; // so the clone is editing its own separate copy of the image
        }
    }

    // Check for $originalFiletype === null to find out if loading it failed

    public static function makeFromFile($filename): ?self
    {
        if ((int) ini_get('memory_limit') < (int) self::MEMORY_LIMIT) {
            ini_set('memory_limit', self::MEMORY_LIMIT);
        } // we were having memory errors when it was 128M before.

        try {
            $data = file_get_contents($filename);
        } catch (\Throwable $e) {
            logError($e->getMessage());
            $data = '';
        }

        return self::makeFromString($data);
    }

    public static function makeFromString($data): ?self
    {
        if ($data === '') {
            return null;
        }

        if ((int) ini_get('memory_limit') < (int) self::MEMORY_LIMIT) {
            ini_set('memory_limit', self::MEMORY_LIMIT);
        } // we were having memory errors when it was 128M before.

        $image = new static;
        $image->originalImageData = $data;

        $image->imagick = new Imagick();

        try {
            $success = $image->imagick->readImageBlob($image->originalImageData);
        } catch (Exception $e) {
            logWarning($e->getMessage());

            return null;
        }

        if (! $success) {
            return null;
        }
        $image->originalFiletype = $image->getImageFormat();

        return $image;
    }

    /*
        Used to apply various edits to pics, as specified in an $edits array.

        $edits:
            borderClip, absoluteWidth, absoluteHeight, maxWidth, maxHeight,
            cropVerticalPositionRatio - (0.0 - 1.0 portion to crop of top vs bottom when scaling for absoluteWidth/Height),
            brightness, saturation, gamma, rotate, contrast, enhance, despekle, opacity (0.0 - 1.0),
            watermarkImage, watermarkHeight, watermarkOpacity, minWidthToAddWatermark, minHeightToAddWatermark, watermarkPosition
    */

    public function applyEdits($edits): bool
    {
        if (! $this->imagick) {
            throw new \RuntimeException('No image loaded.');
        }

        $imageModified = false;

        $didAutoRotate = $this->autoRotateImage();
        if ($didAutoRotate) {
            $imageModified = true;
        }

        $originalSize = $this->getImageDimensions();
        if (! $originalSize || ! $originalSize['width'] || ! $originalSize['height']) {
            throw new \RuntimeException('Invalid image dimensions.');
        }

        if (! empty($edits['borderClip'])) {
            // Remove a number of pixels from all sides of the pic (to zoom into the middle, or to remove junk from the edges)
            $originalSize['width'] -= $edits['borderClip'] * 2;
            $originalSize['height'] -= $edits['borderClip'] * 2;
            $this->imagick->cropImage($originalSize['width'], $originalSize['height'], $edits['borderClip'], $edits['borderClip']);
            $imageModified = true;
        }

        // Resize

        $newWidth = $originalSize['width'];
        $newHeight = $originalSize['height'];

        if (! empty($edits['absoluteWidth']) && ! empty($edits['absoluteHeight'])) {
            // Crops as needed to make it fit those dimensions (such as for a thumbnail)
            $scale = $originalSize['height'] / $edits['absoluteHeight'];
            $scaledWidth = $originalSize['width'] / $scale;
            $scaledHeight = $edits['absoluteHeight'];
            $trimFromSides = $scale * ($scaledWidth - $edits['absoluteWidth']) / 2;
            $trimFromTopBottom = 0;
            if ($scaledWidth < $edits['absoluteWidth']) { // image is too tall/narrow, scale down by height instead of width
                $scale = $originalSize['width'] / $edits['absoluteWidth']; // scale based on thumbWidth instead
                $scaledWidth = $edits['absoluteWidth'];
                $scaledHeight = $originalSize['height'] / $scale;
                $trimFromSides = 0;
                $trimFromTopBottom = $scale * ($scaledHeight - $edits['absoluteHeight']) / 2;
            }
            if ($trimFromSides || $trimFromTopBottom) {
                if (isset($edits['cropVerticalPositionRatio'])) {
                    $trimFromTop = round($trimFromTopBottom * $edits['cropVerticalPositionRatio']);
                } else {
                    $trimFromTop = $trimFromTopBottom;
                }
                $this->imagick->cropImage(
                    (int) ($originalSize['width'] - $trimFromSides * 2),
                    (int) ($originalSize['height'] - $trimFromTopBottom * 2),
                    $trimFromSides,
                    $trimFromTop
                );
                $imageModified = true;
                $originalSize = $this->imagick->getImageGeometry(); // update the dimensions values
            }
            $newWidth = $edits['absoluteWidth'];
            $newHeight = $edits['absoluteHeight'];
        } elseif (! empty($edits['absoluteWidth'])) {
            $newWidth = $edits['absoluteWidth'];
            $newHeight = round($originalSize['height'] / ($originalSize['width'] / $newWidth));
        } elseif (! empty($edits['absoluteHeight'])) {
            $newHeight = $edits['absoluteHeight'];
            $newWidth = round($originalSize['width'] / ($originalSize['height'] / $newHeight));
        } elseif (! empty($edits['maxWidth']) || ! empty($edits['maxHeight'])) {
            $scale = $originalSize['width'] / $originalSize['height'];
            if (! empty($edits['maxWidth']) && $originalSize['width'] > $edits['maxWidth']) {
                $newWidth = $edits['maxWidth'];
                $newHeight = round($newWidth / $scale);
            }
            if (! empty($edits['maxHeight']) && $newHeight > $edits['maxHeight']) {
                $newHeight = $edits['maxHeight'];
                $newWidth = round($newHeight * $scale);
            }
        }

        if ($newHeight !== $originalSize['height'] || $newWidth !== $originalSize['width']) {
            // We do the depth/gamma stuff to fix issues with resize expecting linear gamma.
            // See http://www.imagemagick.org/discourse-server/viewtopic.php?t=15955
            // See http://www.4p8.com/eric.brasseur/gamma.html
            $originalDepth = $this->imagick->getImageDepth();
            //dd($this->imagick->getImageGamma());
            $this->imagick->setImageDepth(16);
            $this->imagick->gammaImage(1 / 2.2);
            $this->imagick->resizeImage($newWidth, $newHeight, Imagick::FILTER_LANCZOS, true);
            $this->imagick->gammaImage(2.2);
            $this->imagick->setImageDepth($originalDepth);
            $imageModified = true;
        }

        if (! empty($edits['opacity'])) {
            $this->imagick->setImageOpacity($edits['opacity']);
            $imageModified = true;
        }

        if (! empty($edits['brightness']) || ! empty($edits['saturation'])) {
            $this->imagick->modulateImage(100 + ($edits['brightness'] ?? 0), 100 + ($edits['saturation'] ?? 0), 100);
            $imageModified = true;
        }
        if (! empty($edits['gamma'])) {
            $this->imagick->gammaImage($edits['gamma']);
            $imageModified = true;
        }
        if (! empty($edits['rotate'])) {
            $this->imagick->rotateimage('#000', $edits['rotate']);
            $imageModified = true;
        }
        if (! empty($edits['contrast'])) {
            $this->imagick->contrastImage((int) $edits['contrast'] === 1);
            $imageModified = true;
        }
        if (! empty($edits['enahance'])) {
            $this->imagick->enhanceImage();
            $imageModified = true;
        }
        if (! empty($edits['despeckle'])) {
            $this->imagick->despeckleImage();
            $imageModified = true;
        }
        if (! empty($edits['blur'])) {
            $this->imagick->blurImage($edits['blur'], 3);
            $imageModified = true;
        }

        // Watermark

        if (! empty($edits['watermarkImage']) &&
            (! empty($edits['minWidthToAddWatermark']) || $newWidth > $edits['minWidthToAddWatermark']) &&
            (! empty($edits['minHeightToAddWatermark']) || $newHeight > $edits['minHeightToAddWatermark'])) {
            $watermarkSource = new Imagick($edits['watermarkImage']);
            $watermarkSourceSize = $watermarkSource->getImageGeometry();
            if (! $watermarkSource || ! $watermarkSourceSize) {
                throw new \RuntimeException("Couldn't get the watermark source '$edits[watermarkImage]'.");
            }

            $watermarkWidth = round($edits['watermarkHeight'] * ($watermarkSourceSize['width'] / $watermarkSourceSize['height']));

            $watermarkSource->scaleImage($watermarkWidth, $edits['watermarkHeight']);
            $watermarkSource->setImageOpacity($edits['watermarkOpacity']);

            switch ($edits['watermarkPosition'] ?? 'bottom right') { // randomly one of the 3 corners (other than top left)
                case 'top right':
                    $this->imagick->compositeImage($watermarkSource, imagick::COMPOSITE_OVER, -2 + $newWidth - $watermarkWidth, 2);
                    break;
                case 'bottom left':
                    $this->imagick->compositeImage($watermarkSource, imagick::COMPOSITE_OVER, 2, -2 + $newHeight - $edits['watermarkHeight']);
                    break;
                case 'bottom right':
                    $this->imagick->compositeImage($watermarkSource, imagick::COMPOSITE_OVER, -2 + $newWidth - $watermarkWidth, -2 + $newHeight - $edits['watermarkHeight']);
                    break;
            }
            $imageModified = true;
            $watermarkSource->clear(); // free resources
        }

        debugOutput('ImageProcessor::applyEdits() -> modified:' . ($imageModified ? 'true' : 'false'));

        return $imageModified;
    }

    public function saveOriginalImageData($outputFilename)
    {
        debugOutput("ImageProcessor::saveOriginalImageData($outputFilename)");

        // Note: We do this instead of file_put_contents() because file_put_contents() was using too much memory (maybe a PHP bug?).
        $fp = fopen($outputFilename, 'wb');
        fwrite($fp, (string) $this->originalImageData);
        fclose($fp);
    }

    public function getImageData(): string
    {
        return (string) $this->imagick;
    }

    public function saveAs($outputFilename, $imageFormat, $outputQuality = null, $backgroundColor = '#ffffff')
    {
        debugOutput("ImageProcessor::saveAs($outputFilename)");

        if ($outputQuality === null) {
            $outputQuality = $this->defaultOutputQuality;
        }

        // In case it had a transparent background, use white as the background color
        $this->imagick->setBackgroundColor(new ImagickPixel($backgroundColor));

        /*
        (disabled this because was causing /tmp to fill up the hard drive from a corrupt gif. Not sure this was necessary.)
        $this->imagick = $this->imagick->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
        */

        switch ($imageFormat) {
            case 'jpg':
                $this->imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
                $this->imagick->setInterlaceScheme(Imagick::INTERLACE_PLANE); // progressie JPEG (better user experience, also required for panoramas under iOS 8)
                break;
            case 'webp':
                $this->imagick->setImageFormat('webp');
//                $this->imagick->setImageCompressionQuality(90);
//                $this->imagick->setOption('webp:lossless', 'true');
//                $this->imagick->setOption('webp:emulate-jpeg-size', 'true');
                $this->imagick->setOption('webp:thread-level', '1');
                $this->imagick->setOption('webp:method', '6');
                break;

            default:
                throw new Exception("Unknown output image format '$imageFormat'.");
        }

        $this->imagick->setImageCompressionQuality($outputQuality);
        $this->imagick->stripImage(); // Strip out unneeded meta data
        if ($outputFilename !== null) {
            try {
                unlink($outputFilename);
            } catch (\Throwable $throwable) {
//                logWarning($throwable->getMessage());
            }

            $this->imagick->writeImage($outputFilename);
        }
    }

    public function getImageDimensions()
    {
        return $this->imagick->getImageGeometry();
    }

    public function getImageResolution()
    {
        return $this->imagick->getImageResolution();
    }

    public function setImageResolution($x = 72, $y = 72)
    {
        return $this->imagick->setImageResolution($x, $y);
    }

    public function getImageDataSizeInBytes()
    {
        return strlen($this->originalImageData);
    }

    public function autoRotateImage()
    {
        $orientation = $this->imagick->getImageOrientation();

        $modified = false;
        switch ($orientation) {
            case imagick::ORIENTATION_BOTTOMRIGHT:
                $this->imagick->rotateimage('#000', 180); // rotate 180 degrees
                $modified = true;
                break;

            case imagick::ORIENTATION_RIGHTTOP:
                $this->imagick->rotateimage('#000', 90); // rotate 90 degrees CW
                $modified = true;
                break;

            case imagick::ORIENTATION_LEFTBOTTOM:
                $this->imagick->rotateimage('#000', -90); // rotate 90 degrees CCW
                $modified = true;
                break;
        }

        // Now that it's auto-rotated, make sure the EXIF data is correct in case the EXIF gets saved with the image!
        if ($modified) {
            $this->imagick->setImageOrientation(imagick::ORIENTATION_TOPLEFT);
        }

        return $modified;
    }

    public function releaseResources()
    {
        if ($this->imagick) {
            $this->imagick->clear();
        }
        $this->originalImageData = null;
    }

    /* Private functions */

    private function getImageFormat()
    {
        $imageFormat = $this->imagick->getImageFormat();

        switch ($imageFormat) {
            case 'JPEG':
            case 'JPG':
                return 'jpg';
            case 'GIF':
            case 'GIF87':
                return 'gif';
            case 'PNG':
            case 'PNG8':
            case 'PNG24':
            case 'PNG32':
                return 'png';
            case 'BMP':
            case 'BMP3':
                return 'bmp';
            case 'TIFF':
                return 'tif';
        }

        logWarning("Unknown image format '$imageFormat'.");

        return '';
    }
}
