<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $student_id
 * @property int $company_id
 * @property int|null $supervisor_id
 * @property string|null $campus_logo
 * @property \Carbon\Carbon|null $start_date
 * @property \Carbon\Carbon|null $end_date
 * @property string $status
 */
class Internship extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'company_id',
        'supervisor_id',
        'campus_logo',
        'start_date',
        'end_date',
        'status',
    ];

    public function getCampusLogoUrlAttribute(): ?string
    {
        if ($this->campus_logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->campus_logo)) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($this->campus_logo);
        }

        return asset('images/logo-polsub.png');
    }

    public function getEffectiveCampusLogoRealPath(): ?string
    {
        if ($this->campus_logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->campus_logo)) {
            return \Illuminate\Support\Facades\Storage::disk('public')->path($this->campus_logo);
        }

        $defaultPath = public_path('images/logo-polsub.png');

        return file_exists($defaultPath) ? $defaultPath : null;
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Supervisor::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function evaluation(): HasOne
    {
        return $this->hasOne(Evaluation::class);
    }

    public function importedDocuments(): HasMany
    {
        return $this->hasMany(ImportedDocument::class);
    }
}
