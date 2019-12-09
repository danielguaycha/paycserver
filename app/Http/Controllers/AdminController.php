<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class AdminController extends Controller
{
    public function viewImg(Request $request, $pathFile, $filename){
        $path = storage_path('app/public'.DIRECTORY_SEPARATOR.$pathFile.DIRECTORY_SEPARATOR. $filename);

            if (!File::exists($path)) {
                abort(404);
            }

        $file = \Illuminate\Support\Facades\File::get($path);
        $type = \Illuminate\Support\Facades\File::mimeType($path);

        $response = Response::make($file, 200);
        $response->header("Content-Type", $type);

        return $response;
    }
}
