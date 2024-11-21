<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ModelableFile extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'file_type_key',
        'disk',
        'module_path',
        'mime_type',
        'extension',
        'size',
        'priority',
        'folder_type_key',
        'thumbnail',
        'deleted_at',
    ];

    protected $hidden = [
        'file_type_key',
        'disk',
        'module_path',
        'mime_type',
        'extension',
        'modelable_id',
        'modelable_type',
        'folder_type_key',
    ];

    protected $casts = [];

    // file-type
    public const FILE_TYPE_IMAGE = 1;

    public const FILE_TYPE_VIDEO = 2;

    public const FILE_TYPE_AUDIO = 3;

    public const FILE_TYPE_SPREADSHEET = 4;

    public const FILE_TYPE_PDF = 5;

    public const FILE_TYPE_UNDEFINE = 99;

    // folder-type
    public const FOLDER_TYPE_NONE = 0;

    public const FOLDER_TYPE_BY_MODEL_ID = 1;

    // module-path
    public const MODULE_PATH_USER_AVATAR = 'user-avatars';

    public const MODULE_PATH_MERCHANT_IMAGE = 'merchant-image';

    // Accessors
    public function getPathPrefixAttribute()
    {
        $disk = Storage::disk($this->disk);
        $url = $this->getPath();

        return $disk->url($url);
    }

    public function getFileUrlAttribute()
    {
        return $this->path_prefix.$this->name;
    }

    public function getThumbnailUrlAttribute()
    {
        if ($this->thumbnail != null) {
            return $this->path_prefix.$this->thumbnail;
        }

        return $this->thumbnail;
    }

    // Mutators

    // Relationships
    public function modelable()
    {
        return $this->morphTo();
    }

    // Extend Relationships

    // Methods
    public function getPath(): string
    {
        $path = null;
        switch ($this->folder_type_key) {
            case self::FOLDER_TYPE_BY_MODEL_ID:
                $path = $this->module_path.'/'.$this->modelable_id.'/';
                break;
            default:
                $path = $this->module_path.'/';
                break;
        }

        return $path;
    }

    public function prune(): bool
    {
        $disk = Storage::disk($this->disk);
        $directoryPath = $this->getPath();

        return $disk->delete($directoryPath.$this->name);
    }

    public function pruneDirectory(): bool
    {
        $disk = Storage::disk($this->disk);
        $directoryPath = $this->getPath();

        return $disk->deleteDirectory($directoryPath);
    }

    // Static Methods
    public static function defaultImage(string $name = 'default-rectangle.png', string $modulePath = 'images/default', string $disk = 'assets'): array
    {
        return [
            'name' => $name,
            'module_path' => $modulePath,
            'disk' => $disk,
            'file_type_key' => self::FILE_TYPE_IMAGE,
            'folder_type_key' => self::FOLDER_TYPE_NONE,
        ];
    }
}
