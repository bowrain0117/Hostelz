<?php

namespace App\Services\Imported;

use App\Models\Imported;
use App\Models\Pic;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Lib\ImageProcessor;

class DownloadPicsImported
{
    public const DATE_RANGE_MONTH = 3;

    public function __construct(
        private readonly Imported $imported,
        private readonly bool $isForce,
    ) {
    }

    public static function create(Imported $imported, bool $isForce = false): self
    {
        return new static($imported, $isForce);
    }

    public function execute(): bool
    {
        if ($this->isSkipDownload()) {
            return false;
        }

        $existingPics = $this->imported->picsObjects;

        $maxPicsCount = $this->imported->status === Imported::STATUS_ACTIVE ? Imported::MAX_PICS_PER_IMPORTED : Imported::INACTIVE_PICS_COUNT;
        $picsUrls = collect($this->imported->pics)->slice(0, $maxPicsCount);

        $deletedPics = $this->getDeletePics($existingPics, $picsUrls);

        $picsUrls->each(
            fn ($picURL, $picNum) => $this->downloadPic($existingPics, $picURL, $picNum, $deletedPics)
        );

        // If there are fewer new pics than there were old pics, delete the leftover pics
        $deletedPics->each(fn (Pic $pic) => $pic->delete());

        $this->imported->load('picsObjects');

        return true;
    }

    //  pics that not exists in $picsUrls
    private function getDeletePics(Collection $existingPics, Collection $picsUrls): Collection
    {
        return $this->isForce
            ? $existingPics
            : $existingPics->reject(function ($item) use ($picsUrls) {
                return $picsUrls->first(fn ($picUrl) => str($picUrl)->startsWith($item->source));
            });
    }

    private function downloadPic(Collection $existingPics, $picURL, $picNum, Collection $deletedPics): void
    {
        if (! $this->isForce) {
            $existPic = $existingPics->first(fn ($pic) => str($picURL)->startsWith($pic->source));

            if ($existPic) {
                $existPic->update(['picNum' => $picNum]);

                return;
            }
        }

        $image = ImageProcessor::makeFromFile($picURL);
        if (! $image) {
            return;
        }

        $dimensions = $image->getImageDimensions();
        if ($dimensions['width'] < Imported::PIC_MIN_WIDTH || $dimensions['height'] < Imported::PIC_MIN_HEIGHT) {
            return;
        }

        if ($deletedPics->isNotEmpty()) {
            // We re-use the existing pic objects (to keep the ID from incrementing out of control,
            // and so there aren't as many 404 errors with the existing listing's pics while we do this update.
            /** @var Pic $pic */
            $pic = $deletedPics->pop();
            $pic->deletePicFiles(); // delete the old pic data first
        } else {
            $pic = new Pic([
                'subjectType' => 'imported',
                'subjectID' => $this->imported->id,
                'type' => 'listings',
            ]);
        }

        DB::transaction(function () use ($pic, $image, $picNum, $picURL) {
            $pic->setAttributesFromImage($image);
            $pic->status = 'ok';
            $pic->picNum = $picNum;
            $pic->source = $picURL;
            $pic->lastUpdate = now()->format('Y-m-d');
            $pic->save();

            $pic->saveImageFiles($image, config('pics.importedOptions'));
        });
    }

    private function isSkipDownload(): bool
    {
        if (! $this->imported->pics) {
            return true;
        }

        $existingPics = $this->imported->picsObjects;

        // check last update
        if (! $this->isForce && $existingPics->isNotEmpty() && $this->imported->status === Imported::STATUS_ACTIVE) {
            $lastUpdatePlusRange = Carbon::createFromFormat(
                'Y-m-d',
                $existingPics->sortBy('lastUpdate')->first()->lastUpdate
            )->addMonths(self::DATE_RANGE_MONTH);

            if ($lastUpdatePlusRange->greaterThan(now())) {
                return true;
            }
        }

        return false;
    }
}
