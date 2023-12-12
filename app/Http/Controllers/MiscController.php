<?php

namespace App\Http\Controllers;

use App;
use App\Models\Ad;
use App\Models\Article;
use App\Models\Pic;
use App\Models\Redirect as PrettyLinks;
use App\SpiderTrap;
use Exception;
use Redirect;
use Response;

class MiscController extends Controller
{
    use App\Traits\Articles;
    use App\Traits\StaticPages;

    public function adClick($adID)
    {
        $ad = Ad::areLive()->where('id', $adID)->first();
        if (! $ad) {
            return;
        }
        $ad->recordClick();
        if ($ad->linkURL == '') {
            throw new Exception("No link URL for ad $ad->id.");
        }

        return Redirect::to($ad->linkURL, 302);
    }

    public function robotsTxtStaticDomain()
    {
        return $this->getRobotsTxt('static');
    }

    public function robotsTxtDynamicDomain()
    {
        return $this->getRobotsTxt('dynamic');
    }

    private function getRobotsTxt($domain)
    {
        $exclusiveContent = Article::where([
            ['placementType', 'exclusive content'],
            ['status', 'published'],
        ])
            ->get()
            ->map(fn (Article $item) => $item->getUrl('relative'));

        $prettyLinks = PrettyLinks::where([
            ['tag', 'prettylink'],
        ])
            ->get('old_url')
            ->map(fn (PrettyLinks $link) => str_replace(
                routeURL('home', [], 'absolute'),
                '',
                $link->old_url)
            );

        $disallowLinks = $exclusiveContent->merge($prettyLinks);

        $output = view(
            'robots-txt',
            [
                'domain' => $domain,
                'disallowLinks' => $disallowLinks,
            ]
        )->render();

        return trimLines($output);
    }

    /*
    public function spiderTrap()
    {
        return SpiderTrap::trapPageVisit();
    }
    */

    // Used for when we want a route to return a not found error

    public function pageNotFound(): void
    {
        App::abort(404);
    }

    // Used to stream pic data for pics that are stored in private cloud storage

    public function cloudStreamedPic(Pic $pic, $sizeType)
    {
        // Check to see if the user is authorized to stream this type of pic

        if (! auth()->check() || ! auth()->user()->hasPermission('staff')) {
            switch ($pic->subjectType) {
                case 'reviews':
                    // For now we don't bother to check to see if the reviewer uploaded the pic. Just let any reviewer view reviewer pics.
                    if (! auth()->check() || ! auth()->user()->hasPermission('reviewer')) {
                        App::abort(403);
                    }

                    break;

                case 'hostels':
                    if (! auth()->check() || ! auth()->user()->userCanEditListing($pic->subjectID)) {
                        App::abort(403);
                    }

                    break;

                default:
                    throw new Exception('Unknown subjectType.');
            }
        }

        return $response = Response::make($pic->getImageData($sizeType), 200, [
            'Content-type' => 'image/jpeg',
        ]);
    }
}
