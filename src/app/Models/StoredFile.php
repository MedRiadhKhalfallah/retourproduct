<?php

namespace App\Models;

use Database\Factories\StoredFileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoredFile extends Model
{
    /** @use HasFactory<StoredFileFactory> */
    use HasFactory;

    public const DATABASE_DISK = 'database';

    public const PATH_PREFIX = 'database:';

    protected $fillable = ['path', 'mime_type', 'content_base64'];

    protected $hidden = ['content_base64'];
}
