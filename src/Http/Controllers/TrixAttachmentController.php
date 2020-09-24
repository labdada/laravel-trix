<?php

namespace Te7aHoudini\LaravelTrix\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Te7aHoudini\LaravelTrix\Models\TrixAttachment;


class TrixAttachmentController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file',
            'modelClass' => 'required',
            'field' => 'required', 
        ]);

        if ($validator->fails()) {
            return response()->json(['errors'=>$validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($this->isImage($request->file->extension()))
        {
            $name = 'content/' . \Str::random(5) . time() . '.' . $request->file->getClientOriginalExtension();

            $image = \Image::make($request->file)->resize(1200, null, function ($constraint) {
                $constraint->aspectRatio();
            });

            $attachment = \Storage::put($name, (string) $image->encode());
        } else {
            $name = $request->file->store('/content');
        }

        TrixAttachment::create([
            'field' => $request->field,
            'attachable_type' => $request->modelClass,
            'attachment' => $name,
            'disk' => $request->disk ?? config('laravel-trix.storage_disk'),
        ]);

        return response()->json(['url' => \Storage::url($name)], Response::HTTP_CREATED);
    }

    public function destroy($url)
    {
        $attachment = TrixAttachment::where('attachment', basename($url))->first();

        return response()->json(optional($attachment)->purge());
    }

    private function isImage($extension)
    {
        return in_array($extension, ['jpeg', 'jpg', 'png']);
    }
}
