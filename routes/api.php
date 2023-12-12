<?php

use App\Services\ImportSystems\BookHostels\APIBookHostels;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');
*/

Route::get('/hostelworld/active/{id}', function (Request $request) {
    //  todo: add checking domain hostelsgeeks

    $id = $request->id;

    $data = APIBookHostels::doRequest(
        'propertyinformation',
        ['PropertyNumber' => $id],
        35, 2
    );

//    logNotice($request->getHost());

    return ['active' => $data['success']];
})->withoutMiddleware(['throttle:api']);
