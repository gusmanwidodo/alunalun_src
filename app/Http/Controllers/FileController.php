<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Input;
use Intervention\Image\Facades\Image;
use Illuminate\Http\Request;
use Validator;

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
        $width = Input::get('width', 800);

        $path_available = ['images', 'tourism', 'product', 'company', 'region', 'news', 'payments'];

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
                if(Input::get('height') && Input::get('x') && Input::get('y')){
                    $img = Image::make(storage_path('app/'. $dir . $name))->fit(Input::get('fit'));
                    $img->crop($width, Input::get('height'), Input::get('x'), Input::get('y'));
                }
                break;
            default:
                // only image file
                $img = Image::make(storage_path('app/'. $dir . $name))->resize($width, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $img->save(storage_path('app/'. $dir . $name));

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

    /**
    eg: src.alunalun.id/file/upload/{type}
    type also file name on form
    */

    public function uploadFile($type, Request $request){
        $validator = Validator::make($request->all(), [
            $type => 'required|image|max:2000'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'failed', 'msg' => $validator->errors()->first($type)]); 
        }

        $file = $request->file($type);

        $name = uniqid().'_'.time(). '.jpg';
        $image = Image::make($file)->encode('jpg');
        $image_medium = Image::make($file)->encode('jpg');
        $image_small = Image::make($file)->encode('jpg');
        $s3 = Storage::disk('s3_prod');
        switch($type){
            case "profile":
                $dir = 'profile/'.date('Y').'/'.date('m').'/';
                $image->fit(350, 350);
                $image_medium->fit(100, 100);
                $image_small->fit(50, 50);
                $s3->put($dir.$name, $image->stream('jpg')->__toString(), 'public');
                $s3->put($dir.'md_'.$name, $image_medium->stream('jpg')->__toString(), 'public');
                $s3->put($dir.'sm_'.$name, $image_small->stream('jpg')->__toString(), 'public');
                break;
            case "logo":
                $dir = 'logos/'.date('Y').'/'.date('m').'/';
                $size = $image->width();
                $size = $size < $image->height() ? $image->height() : $size;
                $image->resizeCanvas($size, $size, 'center', false, 'ffffff');
                $image->fit(200, 200);
                $image_medium->resizeCanvas($size, $size, 'center', false, 'ffffff');
                $image_medium->fit(150, 150);
                $image_small->resizeCanvas($size, $size, 'center', false, 'ffffff');
                $image_small->fit(50, 50);
                $s3->put($dir.$name, $image->stream('jpg')->__toString(), 'public');
                $s3->put($dir.'md_'.$name, $image_medium->stream('jpg')->__toString(), 'public');
                $s3->put($dir.'sm_'.$name, $image_small->stream('jpg')->__toString(), 'public');
                break;

            /* START JAGA-JAGA */
            case "region":
                $dir = 'regions/'.date('Y').'/'.date('m').'/';
                $image->fit(200, 200);
                $image_medium->fit(150, 150);
                $image_small->fit(50, 50);
                $s3->put($dir.$name, $image->stream('jpg')->__toString(), 'public');
                $s3->put($dir.'md_'.$name, $image_medium->stream('jpg')->__toString(), 'public');
                $s3->put($dir.'sm_'.$name, $image_small->stream('jpg')->__toString(), 'public');
                break;
            case "company":
                $size = $image->width();
                $size = $size < $image->height() ? $image->height() : $size;
                $image->resizeCanvas($size, $size, 'center', false, 'ffffff');
                $image->fit(200, 200);
                $image_medium->resizeCanvas($size, $size, 'center', false, 'ffffff');
                $image_medium->fit(150, 150);
                $image_small->resizeCanvas($size, $size, 'center', false, 'ffffff');
                $image_small->fit(50, 50);
                $dir = 'companies/'.date('Y').'/'.date('m').'/';
                $s3->put($dir.$name, $image->stream('jpg')->__toString(), 'public');
                $s3->put($dir.'md_'.$name, $image_medium->stream('jpg')->__toString(), 'public');
                $s3->put($dir.'sm_'.$name, $image_small->stream('jpg')->__toString(), 'public');
                break;
            /* END JAGA-JAGA */

            case "product":
                $size = $image->width();
                $size = $size < $image->height() ? $image->height() : $size;
                $image->resizeCanvas($size, $size, 'center', false, 'ffffff');
                $image->fit(800, 800);
                $image_medium->resizeCanvas($size, $size, 'center', false, 'ffffff');
                $image_medium->fit(300, 300);
                $image_small->resizeCanvas($size, $size, 'center', false, 'ffffff');
                $image_small->fit(100, 100);
                $dir = 'products/'.date('Y').'/'.date('m').'/';
                $s3->put($dir.$name, $image->stream('jpg')->__toString(), 'public');
                $s3->put($dir.'md_'.$name, $image_medium->stream('jpg')->__toString(), 'public');
                $s3->put($dir.'sm_'.$name, $image_small->stream('jpg')->__toString(), 'public');
                break;
            case "cover":
                $dir = 'covers/'.date('Y').'/'.date('m').'/';
                $image->fit(930, 300);
                $image_medium->fit(300, 300);
                $image_small->fit(100, 100);
                $s3->put($dir.$name, $image->stream('jpg')->__toString(), 'public');
                $s3->put($dir.'md_'.$name, $image_medium->stream('jpg')->__toString(), 'public');
                $s3->put($dir.'sm_'.$name, $image_small->stream('jpg')->__toString(), 'public');
                break;
            case "payment":
                $dir = 'payments/'.date('Y').'/'.date('m').'/';
                $image->resize(800, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
                Storage::disk('s3_prod')->put($dir.$name, $image->stream('jpg')->__toString(), 'public');
                break;
            case "news":
                $dir = 'news/'.date('Y').'/'.date('m').'/';
                $image->resize(800, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $image_medium->fit(300, 300);
                $image_small->fit(100, 100);
                $s3->put($dir.$name, $image->stream('jpg')->__toString(), 'public');
                $s3->put($dir.'md_'.$name, $image_medium->stream('jpg')->__toString(), 'public');
                $s3->put($dir.'sm_'.$name, $image_small->stream('jpg')->__toString(), 'public');
                break;
            case "post":
                $dir = 'posts/'.date('Y').'/'.date('m').'/';
                $image->resize(800, 600, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $image_medium->fit(300, 300);
                $image_small->fit(100, 100);
                $s3->put($dir.$name, $image->stream('jpg')->__toString(), 'public');
                $s3->put($dir.'md_'.$name, $image_medium->stream('jpg')->__toString(), 'public');
                $s3->put($dir.'sm_'.$name, $image_small->stream('jpg')->__toString(), 'public');
                break;
            case "banner":
                $dir = 'banner/'.date('Y').'/'.date('m').'/';
                /*$image->resize(800, 600, function ($constraint) {
                    $constraint->aspectRatio();
                });*/
                $image_medium->fit(300, 300);
                $image_small->fit(100, 100);
                $s3->put($dir.$name, $image->stream('jpg')->__toString(), 'public');
                $s3->put($dir.'md_'.$name, $image_medium->stream('jpg')->__toString(), 'public');
                $s3->put($dir.'sm_'.$name, $image_small->stream('jpg')->__toString(), 'public');
                break;
            case "image":
                $dir = 'images/'.date('Y').'/'.date('m').'/';
                /*$image->resize(800, 600, function ($constraint) {
                    $constraint->aspectRatio();
                });*/
                $image_medium->fit(300, 300);
                $image_small->fit(100, 100);
                $s3->put($dir.$name, $image->stream('jpg')->__toString(), 'public');
                $s3->put($dir.'md_'.$name, $image_medium->stream('jpg')->__toString(), 'public');
                $s3->put($dir.'sm_'.$name, $image_small->stream('jpg')->__toString(), 'public');
                break;

            /* START JAGA-JAGA */
            case "tourism":
                $dir = 'tourisms/'.date('Y').'/'.date('m').'/';
                $image->resize(800, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $image_medium->fit(300, 300);
                $image_small->fit(100, 100);
                $s3->put($dir.$name, $image->stream('jpg')->__toString(), 'public');
                $s3->put($dir.'md_'.$name, $image_medium->stream('jpg')->__toString(), 'public');
                $s3->put($dir.'sm_'.$name, $image_small->stream('jpg')->__toString(), 'public');
                break;
            case "vacancy":
                $dir = 'jobs/'.date('Y').'/'.date('m').'/';
                $image->resize(800, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $image_medium->fit(300, 300);
                $image_small->fit(100, 100);
                $s3->put($dir.$name, $image->stream('jpg')->__toString(), 'public');
                $s3->put($dir.'md_'.$name, $image_medium->stream('jpg')->__toString(), 'public');
                $s3->put($dir.'sm_'.$name, $image_small->stream('jpg')->__toString(), 'public');
                break;
            /* END JAGA-JAGA */

            default:
                $dir = 'attachments/'.date('Y').'/'.date('m').'/';
                $image->resize(800, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $image_medium->fit(300, 300);
                $image_small->fit(100, 100);
                $s3->put($dir.$name, $image->stream('jpg')->__toString(), 'public');
                $s3->put($dir.'md_'.$name, $image_medium->stream('jpg')->__toString(), 'public');
                $s3->put($dir.'sm_'.$name, $image_small->stream('jpg')->__toString(), 'public');
        }
        $img_url = $s3->exists($dir . $name) ? $s3->url($dir . $name) : $s3->url('images/no-image.png');

        return response()->json(['status' => 'success', 'msg' => null, 'data' => $dir . $name, 'url' => $img_url]);
    }
}
