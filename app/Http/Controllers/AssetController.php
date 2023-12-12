<?php

namespace App\Http\Controllers;

use App;
use InvalidArgumentException;
use Lib\MinifyCSS;
use Lib\MinifyJavascript;
use Response;

class AssetController extends Controller
{
    public function js($name)
    {
        if (! $this->isValidName($name)) {
            App::abort(404);
        }

        try {
            try {
                //  first check in original js files
                if (is_file(public_path("js/{$name}.js"))) {
                    $contents = file_get_contents(public_path("js/{$name}.js"));
                } else {
                    $contents = view('js/' . $name); // regular js path
                }
            } catch (InvalidArgumentException $e) { // template not found
                $contents = view('Lib/js/' . $name); // also try from the Lib/js path
            }
        } catch (InvalidArgumentException $e) { // template not found
            App::abort(404);
        }

        if (App::environment('production')) {
            $contents = MinifyJavascript::minifyString($contents);
        }

        $response = Response::make($contents);
        $response->header('Content-Type', 'application/javascript');

        return $response;
    }

    public function css($name)
    {
        if (! $this->isValidName($name)) {
            App::abort(404);
        }

        try {
            $contents = view('css/' . $name);
        } catch (InvalidArgumentException $e) { // template not found
            App::abort(404);
        }

        if (App::environment('production')) {
            $contents = MinifyCSS::minifyString($contents);
        }

        $response = Response::make($contents);
        $response->header('Content-Type', 'text/css');

        return $response;
    }

    private function isValidName($s)
    {
        return ! preg_match('/[^a-z_\-0-9]/i', $s);
    }
}
