<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'internship_id',
        'supervisor_id',
        'attendance_score',
        'discipline_score',
        'activity_score',
        'report_score',
        'final_score',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'attendance_score' => 'float',
            'discipline_score' => 'float',
            'activity_score' => 'float',
            'report_score' => 'float',
            'final_score' => 'float',
        ];
    }

    public static function calculateFinalScore(float $attendance, float $discipline, float $activity, float $report): float
    {
        return round(($attendance * 0.20) + ($discipline * 0.20) + ($activity * 0.30) + ($report * 0.30), 2);
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Supervisor::class);
    }
}
