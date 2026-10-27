<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'file_hash',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function isImage(): bool
    {
        return in_array(strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp']);
    }
}
