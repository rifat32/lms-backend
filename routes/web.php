<?php

use App\Http\Controllers\SetupController;
use Illuminate\Support\Facades\Route;

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


Route::get('/setup', [SetupController::class, 'setup'])->name('setup');
Route::get('/setup-passport', [SetupController::class, 'setupPassport'])->name('passport');
Route::get('/roleRefresh', [SetupController::class, 'roleRefresh'])->name('roleRefresh');
Route::get('/swagger-refresh', [SetUpController::class, "swaggerRefresh"])->name("swaggerRefresh");
Route::get('/migrate', [SetupController::class, 'migrate'])->name('migrate');

Route::get('/storage-link', [SetupController::class, 'storageLink'])->name('storageLink');