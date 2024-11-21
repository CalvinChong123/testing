<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PublicFile\UploadFileRequest;
use App\Models\PublicFile;
use Illuminate\Support\Str;
use Pylon\FileStorages\StorageManager;

class PublicFileController extends Controller
{
    public function upload(UploadFileRequest $request)
    {
        $payload = $request->validated();

        $disk = PublicFile::DISK_NAME;
        $module_path = PublicFile::MODULE_PATH_CKEDITOR;
        $file = $payload['upload'];
        $file_name = Str::uuid().'.'.$file->extension();
        $file_type_key = PublicFile::FILE_TYPE_UNDEFINE;

        $storageManager = StorageManager::disk($disk);
        $storageManager->setFolderPath($module_path);
        $content = fopen($file, 'r');
        $storageManager->upload($content, $file_name);

        $publicFile = PublicFile::create([
            'name' => $file_name,
            'file_type_key' => $file_type_key,
            'module_path' => $module_path,
            'mime_type' => $file->getMimeType(),
            'extension' => $file->extension(),
            'size' => $file->getSize(),
        ]);

        $response = $publicFile->toCkeditorResult();

        return self::successResponse('Success', $response);
    }
}
