<?php

use App\Http\Controllers\SetupController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::get('/cache-clear', [SetupController::class, 'cacheClear'])->name('cacheClear');
Route::get('/setup', [SetupController::class, 'setup'])->name('setup');
Route::get('/setup-passport', [SetupController::class, 'setupPassport'])->name('passport');
Route::get('/roleRefresh', [SetupController::class, 'roleRefresh'])->name('roleRefresh');
Route::get('/swagger-refresh', [SetUpController::class, "swaggerRefresh"])->name("swaggerRefresh");
Route::get('/migrate', [SetupController::class, 'migrate'])->name('migrate');
Route::get('/rollback-migrate', [SetupController::class, 'rollbackMigration'])->name('rollbackMigration');

Route::get('/storage-link', [SetupController::class, 'storageLink'])->name('storageLink');


Route::get('/storage-proxy/{path}', function ($path) {
    $file_path = storage_path('app/public/' . $path);

    if (!file_exists($file_path)) {
        abort(404);
    }

    $file = file_get_contents($file_path);
    $mime_type = mime_content_type($file_path);

    return Response::make($file, 200)
        ->header('Content-Type', $mime_type)
        ->header('Access-Control-Allow-Origin', '*');
})->where('path', '.*');
