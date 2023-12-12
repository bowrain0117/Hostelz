<?php

/*

Note: This currently requires the Languages class.

*/

namespace Lib;

use App\Models\Languages;
use Exception;
use Request;

class Captcha
{
    public $publicKey;

    public $privateKey;

    public $languageCode;

    public function __construct($publicKey = null, $privateKey = null)
    {
        $this->publicKey = $publicKey ?? config('custom.captchaPublicKey');
        $this->privateKey = $privateKey ?? config('custom.captchaPrivateKey');
    }

    public function headerOutput(): string
    {
        return '<script src="https://www.google.com/recaptcha/api.js?hl=' . Languages::current()->otherCodeStandard('Google') . '"></script>';
    }

    public function formOutput(): string
    {
        return '<div class="g-recaptcha" data-sitekey="' . $this->publicKey . '"></div>';
    }

    public function verify(): bool
    {
        $result = WebsiteTools::fetchPage('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $this->privateKey,
            'response' => Request::input('g-recaptcha-response'),
            'remoteip' => Request::server('REMOTE_ADDR'),
        ]);

        if (! $result) {
            throw new Exception('Captcha returned empty response.');
        }

        $decoded = json_decode($result, true);
        if (! $decoded) {
            throw new Exception("Can't decode captcha response '$result'.");
        }

        if (isset($decoded['success']) && $decoded['success'] == true) {
            return true;
        }

        return false;
    }
}
