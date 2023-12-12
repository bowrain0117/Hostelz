<?php

namespace App\Http\Controllers;

use App\Models\Languages;
use Illuminate\Support\Facades\URL;

class LanguageChangeController extends Controller
{
    public function setLanguage($language)
    {
        Languages::setLanguageLocale($language);

        $redirectUrl = Languages::urlWithLanguage(URL::previous());

        return redirect()->to($redirectUrl);
    }
}
