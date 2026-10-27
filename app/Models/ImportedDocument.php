<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportedDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'internship_id',
        'file_name',
        'file_hash',
        'file_size',
        'activities_count',
    ];

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }
}
