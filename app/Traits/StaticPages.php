<?php

namespace App\Traits;

use App\Http\Controllers\MiscController;
use App\Models\Listing\Listing;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Lib\Captcha;

trait StaticPages
{
    protected $staticPages;

    public static function staticPageRouts(): void
    {
        self::staticPagesOptions()->each(function ($item): void {
            Route::get($item['slug'], [MiscController::class, $item['method']])->name($item['alias'])->middleware('browserCache:3 days public', 'pageCache:indefinite');
        });
    }

    public function about()
    {
        return view('staticPages.about');
    }

    public function partner_with_us()
    {
        return view('staticPages.partner-with-us');
    }

    public function hi()
    {
        return view('staticPages.hi');
    }

    public function faq()
    {
        return view('staticPages.faq');
    }

    public function hiUSA()
    {
        return view('staticPages.hi-usa');
    }

    public function privacyPolicy()
    {
        return view('staticPages.privacyPolicy');
    }

    public function termsConditions()
    {
        return view('staticPages.termsConditions');
    }

    public function linkToUs()
    {
        return view('staticPages.linkToUs');
    }

    public function howCompareHostelPrices()
    {
        $ogThumbnail = url('images', 'how-compare-hostel-prices-hostelz.jpg');

        return view('staticPages.howCompareHostelPrices', compact('ogThumbnail'));
    }

    public function onHold()
    {
        return view('staticPages.onHold');
    }

    public function contactUs($reason = '', $contactType = '')
    {
        $lastViewedPage = $_COOKIE['lastViewed'] ?? null;

        $listing = null;
        if (strstr($lastViewedPage, '/hostel/') || strstr($lastViewedPage, '/hotel/')) {
            preg_match("`/hos?tel/(.*)\-(.*)$`sU", $lastViewedPage, $result);
            $listingID = intval($result[1]);
            if ($listingID) {
                $listing = Listing::find($listingID);
            }
        }

        if ($reason != 'contact-form') {
            return view('staticPages.contact', compact('reason', 'listing'));
        }

        /* Contact Form */

        $errors = null;
        $input = [];
        $status = '';
        $captcha = null;

        if (! auth()->check()) {
            $captcha = new Captcha();
            if (Request::isMethod('post') && ! $captcha->verify()) {
                return view('captchaError');
            }
        }

        if (Request::isMethod('post')) {
            $validations = [
                'name' => '',
                'email' => 'required|email',
                'subject' => 'required',
                'message' => 'required',
            ];

            $input = Request::only(array_keys($validations));

            $validator = Validator::make($input, $validations);
            if ($validator->fails()) {
                $errors = $validator->messages();
            } else {
                $from = str_replace(',', ' ', "From: $input[name] <$input[email]>"); // remove commas
                $subject = $input['subject'];

                // Check for injection exploits
                if (strpos($subject, "\n") !== false || strpos($subject, "\r") !== false ||
                    strpos($from, "\n") !== false || strpos($from, "\r") !== false) {
                    return view('error');
                }

                switch ($contactType) {
                    case 'listings':
                        $sendTo = Config::get('custom.listingSupportEmail');

                        break;

                    case 'press':
                        $sendTo = Config::get('custom.pressSupportEmail');
                        $subject = "PRESS - $subject";

                        break;

                    default:
                        $sendTo = Config::get('custom.userSupportEmail');
                }

                mail($sendTo, $subject, trim("$lastViewedPage\n\n" . $input['message']), "$from\nX-Mailer: PHP (ip: " . $_SERVER['REMOTE_ADDR'] . ')');
                $status = 'sent';
            }
        } elseif (auth()->check()) {
            $input = auth()->user()->getOutgoingEmailInfo(); // (sets 'name' and 'email')
        }

        return view('staticPages.contact', array_merge($input, compact('reason', 'contactType', 'captcha', 'errors', 'status')));
    }

    public static function staticPagesOptions()
    {
        return collect([
            [
                'slug' => 'about',
                'method' => 'about',
                'alias' => 'about',
            ],
            [
                'slug' => 'partner-with-us',
                'method' => 'partner_with_us',
                'alias' => 'partner_with_us',
            ],
            [
                'slug' => 'hi',
                'method' => 'hi',
                'alias' => 'hi',
            ],
            [
                'slug' => 'hi-usa',
                'method' => 'hiUSA',
                'alias' => 'hi-usa',
            ],
            [
                'slug' => 'privacy',
                'method' => 'privacyPolicy',
                'alias' => 'privacy-policy',
            ],
            [
                'slug' => 'terms-conditions',
                'method' => 'termsConditions',
                'alias' => 'termsConditions',
            ],
            [
                'slug' => 'articles/{slug?}',
                'method' => 'articles',
                'alias' => 'articles',
            ],
            [
                'slug' => 'link-to-us',
                'method' => 'linkToUs',
                'alias' => 'linkToUs',
            ],
            [
                'slug' => 'faq',
                'method' => 'faq',
                'alias' => 'faq',
            ],
            [
                'slug' => 'how-to-compare-hostel-prices-hostelz',
                'method' => 'howCompareHostelPrices',
                'alias' => 'howCompareHostelPrices',
            ],
            [
                'slug' => 'on-hold',
                'method' => 'onHold',
                'alias' => 'onHold',
            ],
        ]);
    }
}
