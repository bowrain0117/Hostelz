<?php

namespace App\Lib\Common\Images;

use App\Models\Pic;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Image
{
    public $src;

    public $title;

    public function __construct(Pic|Media|null $pic = null, string $altTitle = '')
    {
        $this->src = $this->getSrcs($pic);
        $this->title = $this->getTitle($pic, $altTitle);
    }

    public static function create($pic, $altTitle = ''): self
    {
        return new static($pic, $altTitle);
    }

    public static function default($altTitle = ''): self
    {
        return new static(null, $altTitle);
    }

    private function getTitle($pic, $altTitle): string
    {
        if ($pic?->caption) {
            return $pic->caption;
        }

        return $altTitle;
    }

    private function getSrcs($pic): array
    {
        if ($pic === null) {
            return $this->getPicSrcs($pic);
        }

        return match (get_class($pic)) {
            Pic::class => $this->getPicSrcs($pic),
            Media::class => $this->getMediaSrcs($pic),
            default => $this->getPicSrcs($pic),
        };
    }

    public function getMediaSrcs(Media $media): array
    {
        return [
            'tiny' => $media?->getFullUrl('tiny'),
            'thumb_def' => $media?->getFullUrl('thumbnail'),
            'thumb_webp' => $media?->getFullUrl('webp_thumbnail'),
            'big' => $media?->getFullUrl(),
            'big_webp' => $media?->getFullUrl('webp_big'),
        ];
    }

    private function getPicSrcs(?Pic $pic): array
    {
        return [
            'thumb_def' => $this->getUrl($pic, ['thumbnails', 'big']),
            'thumb_webp' => $this->getUrl($pic, ['webp_thumbnails', 'big']),
            'tiny' => $this->getUrl($pic, ['tiny', 'big']),
            'big' => $this->getUrl($pic, ['big']),
            'big_webp' => $this->getUrl($pic, ['webp_big', 'big']),
        ];
    }

    private function getUrl(?Pic $pic, $options)
    {
        return $pic
            ? $pic->url($options, 'absolute')
            : Pic::getDefaultImageUrl();
    }
}
