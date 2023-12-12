<?php

namespace App\Http\Controllers;

use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class DocumentationController extends Controller
{
    public function index()
    {
        return view('staff/documentation/index');
    }

    public function edit()
    {
        $content = view('staff/documentation/content');

        return view('staff/documentation/edit', compact('content'));
    }

    public function update(Request $request)
    {
        // todo: maybe validate
        File::put(resource_path() . '/views/staff/documentation/content.blade.php', request('content'));

        Artisan::call('view:clear');

        return redirect()->route('documentation');
    }
}
