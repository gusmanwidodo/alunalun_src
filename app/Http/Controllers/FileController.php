<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Input;
use Intervention\Image\Facades\Image;
use Illuminate\Http\Request;

class FileController extends Controller
{

    public function saveFile(Request $request)
    {
        $file = $request->file('file');

        // config
        $path = Input::get('path') ? Input::get('path') : 'images';
        $dir = $path . '/';
        $id = uniqid();
        $name = $id . '_' . $file->getClientOriginalName();

        Storage::put($dir . $name, file_get_contents($file->getRealPath()));

        // only image file
        if (Input::get('width') && Input::get('height')) {

            $img = Image::make(storage_path('app/'. $dir . $name))->resize(Input::get('width'), Input::get('height'));

            $img->save(storage_path('app/'. $dir . $name));
        }

        if (Input::get('fit')) {

            $img = Image::make(storage_path('app/'. $dir . $name))->fit(Input::get('fit'));

            $img->save(storage_path('app/'. $dir . $name));
        }

        // upload to S3
        Storage::disk('s3')->put($dir . $name, file_get_contents(storage_path('app/'. $dir . $name)), 'public');

        return response()->json(['status' => 'success', 'data' => $name]);
    }

    public function deleteFile($name)
    {
        Storage::delete($name);
        return response()->json('success');
    }

    public function getFileList(){

        $files = Storage::files('/');
        return response()->json($files);

    }

    public function viewFile($name){

        return response()->make(Storage::get($name), 200, [
            'Content-Type' => Storage::mimeType($name),
            'Content-Disposition' => 'inline; '.$name,
        ]);

    }

}
