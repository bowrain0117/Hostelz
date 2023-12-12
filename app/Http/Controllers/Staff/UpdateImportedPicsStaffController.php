<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Imported;

class UpdateImportedPicsStaffController extends Controller
{
    public function __invoke(Imported $imported)
    {
        $imported->downloadPics(isForce: true);

        return redirect(routeURL('staff-importeds', [$imported->id]) . '?objectCommand=forceUpdateImportedPics');
    }
}
