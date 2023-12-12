<?php

namespace App\Http\Controllers;

use App;
use App\Helpers\EventLog;
use App\Models\CityInfo;
use App\Models\Pic;
use App\Models\Review;
use Cache;
use Exception;
use Lib\ImageProcessor;
use Request;
use Response;

/*
Previously, when this script was used to change the brightness or rotation of a photo, the tweaked version at full resolution was saved in the "edited" folder.

Now instead we save a summary of the tweaks applied in the "edits" field of the pics table in case we want to later re-produce them at a different resolution.d
*/

/*
7300 pics
7.3 hours
@ $20/hour
$150
*/

class PicFixController extends Controller
{
    public function picFix($picType)
    {
        $message = '';
        $start = intval(Request::input('page'));

        switch ($picType) {
            case 'instructions':
                return view('staff/picFix-instructions');

            case 'reviews':
                $userCurrentlyDoingPicFixForReviews = Cache::get('userCurrentlyDoingPicFixForReviews');
                if ($userCurrentlyDoingPicFixForReviews && $userCurrentlyDoingPicFixForReviews != auth()->id()) {
                    return 'Sorry, another user is currently editing review photos.  Please try again in a little while.';
                }
                Cache::put('userCurrentlyDoingPicFixForReviews', auth()->id(), 60);

                $previewWidth = 380;
                $picsPerPage = 10;
                $picQuery = Pic::where('subjectType', 'reviews')->where('originalFiletype', '!=', '')->where('status', 'markedForEditing');

                break;

            case 'cityInfo':
                $previewWidth = CityInfo::PIC_WIDTH;
                $picsPerPage = 10;
                $picQuery = Pic::where('subjectType', 'cityInfo')->where('type', 'user')->where('originalFiletype', '!=', '')->where('status', 'new');

                break;

            case 'viewRecentReviewPicEdits':
                $previewWidth = 380;
                $picsPerPage = 10;
                $limit = 200;
                $picIDs = EventLog::where('action', 'edit')->where('subjectType', 'Pic')->orderBy('eventTime', 'DESC')->limit($limit)->pluck('subjectID');
                $picQuery = Pic::where('subjectType', 'reviews')->whereIn('id', $picIDs);

                break;

            default:
                App::abort(404);
        }

        // Handle commands

        switch (Request::input('command')) {
            case 'displayPic':
                $pic = $picQuery->where('id', Request::input('picID'))->first();
                if (! $pic) {
                    App::abort(404);
                }
                $image = ImageProcessor::makeFromString($pic->getImageData('originals'));
                if (! $image) {
                    throw new Exception("Couldn't load image for $pic->id.");
                }
                $edits = Request::input('edits');
                $edits['absoluteWidth'] = $previewWidth;
                $image->applyEdits($edits);
                $image->saveAs(null, 'jpg', 75);

                return $response = Response::make((string) $image->getImageData(), 200, [
                    'Content-type' => 'image/jpeg',
                ]);

            case 'submitAllEdits':
                set_time_limit(15 * 60); // 15 min
                ignore_user_abort(true);

                $message = 'Pics Updated: ';

                $allEdits = json_decode(Request::input('allPicEdits'), true);

                foreach ($allEdits as $picID => $edits) {
                    $pic = with(clone $picQuery)->where('id', $picID)->first();
                    if (! $pic) {
                        continue;
                    } // pic may have been already modified, etc.
                    $image = ImageProcessor::makeFromString($pic->getImageData('originals'));
                    if (! $image) {
                        throw new Exception("Couldn't load image for $pic->id.");
                    }

                    switch ($picType) {
                        case 'reviews':
                        case 'viewRecentReviewPicEdits':
                            $picOutputTypes = Review::picFixPicOutputTypes();
                            $review = Review::find($pic->subjectID);
                            $review->clearRelatedPageCaches();
                            $message .= '<a href="' . routeURL('staff-reviews', $review->id) . '">' . $review->listing->name . '</a> ';

                            break;

                        case 'cityInfo':
                            $picOutputTypes = CityInfo::picFixPicOutputTypes();
                            $cityInfo = CityInfo::find($pic->subjectID);
                            $cityInfo->clearRelatedPageCaches();
                            $message .= '<a href="' . $cityInfo->getURL() . "\">$cityInfo->city</a> ";

                            break;

                        default:
                            throw new Exception("Unknown picType '$picType'.");
                    }

                    foreach ($picOutputTypes as &$picOutputType) {
                        $picOutputType = $picOutputType + $edits;
                    }
                    unset($picOutputType); // break the reference with the last element
                    $pic->saveImageFiles($image, $picOutputTypes);

                    $pic->status = 'ok';
                    $pic->edits = $edits;
                    $pic->save();

                    EventLog::log('staff', 'edit', 'Pic', $pic->id, '', json_encode($edits));
                }

                break;
        }

        $totalCount = with(clone $picQuery)->count();

        $pics = $picQuery->orderBy('subjectID')->orderBy('id')/*->skip($start)->take($picsPerPage)*/;
        $pagination = $pics->paginate($picsPerPage);

        $pics = $pics->get();

        $parameters = [
            // 'brightness' no longer used, just using gamma instead
            'gamma' => [
                'label' => 'B',
                'options' => ['-5' => 0.5, '-4' => 0.6, '-3' => 0.7, '-2' => 0.8, '-1' => 0.9, '0' => 0,
                    '1' => 1.1, '2' => 1.2, '3' => 1.4, '4' => 1.6, '5' => 1.8, ],
                'defaultValue' => 0,
            ],
            'saturation' => [
                'label' => 'S',
                'options' => ['-5' => -50, '-4' => -30, '-3' => -20, '-2' => -10, '-1' => -5, '0' => 0,
                    '1' => 5, '2' => 10, '3' => 20, '4' => 30, '5' => 50, ],
                'defaultValue' => 0,
            ],
            'contrast' => [
                'label' => 'C',
                'options' => ['-1' => -1, '0' => 0, '1' => 1],
                'defaultValue' => 0,
            ],
            'rotate' => [
                'label' => 'R',
                'options' => ['2&larr;' => -180, '&larr;' => -90, 'X' => 0, '&rarr;' => 90, '2&rarr;' => 180],
                'defaultValue' => 0,
            ],
        ];

        return view('staff/picFix', compact('message', 'totalCount', 'start', 'pics', 'picType', 'previewWidth', 'parameters', 'pagination'));
    }
}
