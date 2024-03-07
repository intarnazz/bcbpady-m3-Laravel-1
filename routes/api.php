<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

use App\Http\Controllers\UserController;
use App\Http\Controllers\FileController;

Route::get('/', function () {
  return view('welcome');
});

Route::post('/registration', [UserController::class, 'reg']);
Route::post('/authorization', [UserController::class, 'login']);
Route::get('/LoginFailed', [UserController::class, 'LoginFailed'])->name('login');

Route::middleware('auth:api')->group(function () {
  Route::get('/files/disk', [FileController::class, 'getFiles']);
  Route::get('/shared', [FileController::class, 'shared']);

  Route::post('/logout', [UserController::class, 'logout']);
  Route::post('/files', [FileController::class, 'addFile']);
  Route::patch('/files/{file_id}', [FileController::class, 'FileChange']);
  Route::delete('/files/{file_id}', [FileController::class, 'FileDelete']);
  Route::get('/files/{file_id}', [FileController::class, 'getFile']);
  Route::post('/files/{file_id}/accesses', [FileController::class, 'addAccess']);
  Route::delete('/files/{file_id}/accesses', [FileController::class, 'deleteAccess']);
});


//При попытке доступа гостя к защищенным авторизацией функциям системы во всех запросах необходимо возвращать ответ следующего вида:
//
//Status: 403
//Content-Type: application/json
//Body:
//{
//  "message": "Login failed"
//}
//
//При попытке доступа авторизованным пользователем к функциям недоступным для него во всех запросах необходимо возвращать ответ следующего вида:
//
//Status: 403
//Content-Type: application/json
//Body:
//{
//  "message": "Forbidden for you"
//}
//
//При попытке получить не существующий ресурс необходимо возвращать ответ следующего вида:
//
//Status: 404
//Content-Type: application/json
//Body:
//{
//  "message": "Not found"
//}
//
//В случае ошибок связанных с валидацией данных во всех запросах необходимо возвращать следующее тело ответа:
//
//Status: 422
//Content-Type: application/json
//Body:
//{
//  "success": false,
//   "message": {
//  <key>: [<error message>]
//      }
//}
