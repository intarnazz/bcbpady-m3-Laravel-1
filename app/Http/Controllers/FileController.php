<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmailRequest;
use App\Http\Requests\FileNameRequest;
use App\Models\Access;
use App\Models\File;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileController extends Controller
{
  public function addFile(Request $request)
  {
    $res = [];
    $user = auth()->user();
    $files = $request->allFiles();
    foreach ($files as $file) {
      $name = $file->getClientOriginalName();
      $file_name = pathinfo($name, PATHINFO_FILENAME);
      $file_extension = pathinfo($name, PATHINFO_EXTENSION);
      $OriginalFileName = $file_name;
      if (
        filesize($file) < 1024 * 1024 * 2
        && ($file_extension == 'doc'
          || $file_extension == 'pdf'
          || $file_extension == 'docx'
          || $file_extension == 'zip'
          || $file_extension == 'jpeg'
          || $file_extension == 'jpg'
          || $file_extension == 'png'
        )
      ) {
        for ($i = 1; ; $i++) {
          $exists = File::where('name', ($file_name . '.' . $file_extension))
            ->exists();
          if (!$exists) {
            break;
          }
          $file_name = ($OriginalFileName . " ($i)");
        }
        for (; ;) {
          $fileRandomName = Str::random(10);
          $exists = File::where('path', 'like', ('file' . $fileRandomName . "%"))
            ->exists();
          if (!$exists) {
            break;
          }
        }
        $path = $file->storeAs(
          'file', ($fileRandomName . '.' . $file_extension)
        );
        $file = new File();
        $file->user_id = $user->id;
        $file->name = ($file_name . "." . $file_extension);
        $file->path = $path;
        $file->save();

        $res[] = [
          'success' => true,
          'message' => 'success',
          'name' => $file->name,
          'url' => (env('API_URL') . "file/" . $fileRandomName),
          'file_id' => $fileRandomName,
        ];
      } else {
        $res[] = [
          'success' => false,
          'message' => 'File not loaded',
          'name' => $name,
        ];
      }
    }
    return response($res);
  }

  public function FileChange($file_id, FileNameRequest $request)
  {
    $res = [];
    $user = auth()->user();
    $file = File::where('path', 'like', ('file/' . $file_id . "%"))
      ->first();
    if (!$file) {
      return response([
        'message' => 'Not found',
      ], 404);
    }
    if ($file->user_id != $user->id) {
      $access = Access::where('user_id', $user->id)
        ->where('file_id', $file->id)
        ->exists();
      if (!$access) {
        return response([
          'message' => 'Forbidden for you',
        ], 403);
      }
    }

    $file_name = $request->name;
    $file_extension = pathinfo($file->name, PATHINFO_EXTENSION);
    $OriginalFileName = $file_name;

    for ($i = 1; ; $i++) {
      $exists = File::where('name', ($file_name . '.' . $file_extension))
        ->exists();
      if (!$exists) {
        break;
      }
      $file_name = ($OriginalFileName . " ($i)");
    }
    $file->name = ($file_name . "." . $file_extension);
    return response([
      'success' => true,
      'message' => 'Renamed',
    ]);
  }

  public function FileDelete($file_id)
  {
    $res = [];
    $user = auth()->user();
    $file = File::where('path', 'like', ('file/' . $file_id . "%"))
      ->first();
    if (!$file) {
      return response([
        'message' => 'Not found',
      ], 404);
    }
    if ($file->user_id != $user->id) {
      $access = Access::where('user_id', $user->id)
        ->where('file_id', $file->id)
        ->exists();
      if (!$access) {
        return response([
          'message' => 'Forbidden for you',
        ], 403);
      }
    }
    Storage::delete($file->path);
    $file->delete();
    return response([
      'success' => true,
      'message' => 'File already deleted',
    ]);
  }

  public function getFile($file_id)
  {
    $res = [];
    $user = auth()->user();
    $file = File::where('path', 'like', ('file/' . $file_id . "%"))
      ->first();
    if (!$file) {
      return response([
        'message' => 'Not found',
      ], 404);
    }
    if ($file->user_id != $user->id) {
      $access = Access::where('user_id', $user->id)
        ->where('file_id', $file->id)
        ->exists();
      if (!$access) {
        return response([
          'message' => 'Forbidden for you',
        ], 403);
      }
    }
    return Storage::download($file->path);
  }

  public function addAccess($file_id, EmailRequest $request)
  {
    $user = auth()->user();
    $res[] = [
      'fullname' => ($user->first_name . ' ' . $user->last_name),
      'email' => $user->email,
      'type' => 'author',
    ];
    $file = File::where('path', 'like', ('file/' . $file_id . "%"))
      ->first();
    if (!$file) {
      return response([
        'message' => 'Not found',
      ], 404);
    }
    if ($file->user_id != $user->id) {
      return response([
        'message' => 'Forbidden for you',
      ], 403);
    }
    $user = User::where('email', $request->email)
      ->first();
    if (!$user) {
      return response([
        'message' => 'Not found',
      ], 404);
    }
    $access = Access::where('user_id', $user->id)
      ->where('file_id', $file->id)
      ->exists();
    if (!$access) {
      $access = new Access();
      $access->user_id = $user->id;
      $access->file_id = $file->id;
      $access->save();
    }
    $accesses = Access::where('file_id', $file->id)
      ->with('user')
      ->get();
    foreach ($accesses as $access) {
      $res[] = [
        'fullname' => ($access->user->first_name . ' ' . $access->user->last_name),
        'email' => $access->user->email,
        'type' => 'co-author',
      ];
    }
    return response($res);
  }

  public function deleteAccess($file_id, EmailRequest $request)
  {
    $user = auth()->user();
    $res[] = [
      'fullname' => ($user->first_name . ' ' . $user->last_name),
      'email' => $user->email,
      'type' => 'author',
    ];
    if ($user->email == $request->email) {
      return response([
        'message' => 'Forbidden for you',
      ], 403);
    }
    $file = File::where('path', 'like', ('file/' . $file_id . "%"))
      ->first();
    if (!$file) {
      return response([
        'message' => 'Not found',
      ], 404);
    }
    if ($file->user_id != $user->id) {
      return response([
        'message' => 'Forbidden for you',
      ], 403);
    }
    $user = User::where('email', $request->email)
      ->first();
    if (!$user) {
      return response([
        'message' => 'Not found',
      ], 404);
    }
    $access = Access::where('user_id', $user->id)
      ->where('file_id', $file->id)
      ->first();
    if (!$access) {
      return response([
        'message' => 'Not found',
      ], 404);
    }
    $access->delete();

    $accesses = Access::where('file_id', $file->id)
      ->with('user')
      ->get();
    foreach ($accesses as $access) {
      $res[] = [
        'fullname' => ($access->user->first_name . ' ' . $access->user->last_name),
        'email' => $access->user->email,
        'type' => 'co-author',
      ];
    }
    return response($res);
  }

  public function getFiles()
  {
    $user = auth()->user();
    $res = [];
    $files = File::where('user_id', $user->id)
      ->get();
    foreach ($files as $file) {
      $accesses = Access::where('file_id', $file->id)
        ->with('user')
        ->get();
      $accessRes = [];
      $accessRes[] = [
        'fullname' => ($user->first_name . ' ' . $user->last_name),
        'email' => $user->email,
        'type' => 'author',
      ];
      foreach ($accesses as $access) {
        $accessRes[] = [
          'fullname' => ($access->user->first_name . ' ' . $access->user->last_name),
          'email' => $access->user->email,
          'type' => 'co-author',
        ];
      }
      $file_id = pathinfo($file->path, PATHINFO_FILENAME);
      $res[] = [
        'file_id' => $file_id,
        'name' => $file->name,
        'url' => (env('APP_URL') . 'files/' . $file_id),
        'accesses' => $accessRes,
      ];
    }
    return response($res);
  }

  public function shared()
  {
    $user = auth()->user();
    $res = [];
    $accesses = Access::where('user_id', $user->id)
      ->with('file')
      ->get();
    foreach ($accesses as $access) {
      $file_id = pathinfo($access->file->path, PATHINFO_FILENAME);
      $res[] = [
        'file_id' => $file_id,
        'name' => $access->file->name,
        'url' => (env('APP_URL') . 'files/' . $file_id),
      ];
    }
    return response($res);
  }
}
