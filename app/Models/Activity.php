<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $internship_id
 * @property int $day_number
 * @property \Carbon\Carbon|null $activity_date
 * @property string $work_field
 * @property string $activities
 * @property string|null $tools_used
 * @property string|null $problems
 * @property string|null $solutions
 * @property string $status
 */
class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'internship_id',
        'day_number',
        'activity_date',
        'work_field',
        'activities',
        'tools_used',
        'problems',
        'solutions',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
        ];
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ActivityAttachment::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class);
    }

    public function latestApproval(): HasOne
    {
        return $this->hasOne(Approval::class)->latestOfMany();
    }
}
