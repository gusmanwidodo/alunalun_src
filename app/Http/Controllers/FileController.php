<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Input;
use Intervention\Image\Facades\Image;
use Illuminate\Http\Request;

class FileController extends Controller
{
    /*
    1. Make sure path in path available
    2. Action available
        a. Fit
    */
    public function saveFile(Request $request)
    {
        $file = $request->file('file');
        if(!Input::get('width') || !Input::get('height')) abort(404);

        $path_available = ['images', 'tourism', 'product', 'company', 'region', 'news'];

        // config
        $path = Input::get('path') ? Input::get('path') : 'images';
        $dir = $path . '/';
        $id = uniqid().'_'.time();
        $name = $id . '_' . $file->getClientOriginalName();

        Storage::put($dir . $name, file_get_contents($file->getRealPath()));

        $img = null;
        switch(Input::get('action')){
            case "fit":
                $img = Image::make(storage_path('app/'. $dir . $name))->fit(Input::get('fit'));
                $img->save(storage_path('app/'. $dir . $name));
                break;
            case "crop":
                if(Input::get('width') && Input::get('height') && Input::get('x') && Input::get('y')){
                    $img = Image::make(storage_path('app/'. $dir . $name))->fit(Input::get('fit'));
                    $img->crop(Input::get('width'), Input::get('height'), Input::get('x'), Input::get('y'));
                }
                break;
            default:
                // only image file
                if (Input::get('width') && Input::get('height')) {
                    $img = Image::make(storage_path('app/'. $dir . $name))->resize(Input::get('width'), Input::get('height'));
                    $img->save(storage_path('app/'. $dir . $name));
                }

        }

        // upload to S3
        if($img) Storage::disk('s3')->put($dir . $name, file_get_contents(storage_path('app/'. $dir . $name)), 'public');
        Storage::delete(storage_path('app/'. $dir . $name));

        return response()->json(['status' => 'success', 'data' => $dir . $name]);
    }

    public function cropImage(Request $request){
        // open file a image resource
        $img = Image::make('public/foo.jpg');

        // crop image
        $img->crop(100, 100, 25, 25);
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
