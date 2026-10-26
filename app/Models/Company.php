<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'address',
        'field_supervisor',
        'field_supervisor_position',
    ];

    public function internships(): HasMany
    {
        return $this->hasMany(Internship::class);
    }
}
