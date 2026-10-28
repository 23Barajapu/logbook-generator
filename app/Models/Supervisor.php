<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supervisor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_number',
        'position',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function internships(): HasMany
    {
        return $this->hasMany(Internship::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    /**
     * Cari supervisor yang sudah ada atau buat baru dari input nama/nip.
     */
    public static function resolveOrCreate(?string $supervisorId, ?string $name, ?string $nip = null, ?string $position = null, ?string $email = null): ?self
    {
        if (! empty($supervisorId)) {
            $found = self::find($supervisorId);
            if ($found) {
                return $found;
            }
        }

        $trimmedName = trim((string) $name);
        if ($trimmedName === '') {
            return null;
        }

        $trimmedNip = ! empty($nip) ? trim((string) $nip) : null;

        // 1. Coba cari berdasarkan NIP jika disediakan
        if ($trimmedNip) {
            $existing = self::where('employee_number', $trimmedNip)->first();
            if ($existing) {
                return $existing;
            }
        }

        // 2. Coba cari berdasarkan nama user role pembimbing
        $existingUser = User::where('role', 'pembimbing')->where('name', $trimmedName)->first();
        if ($existingUser && $existingUser->supervisor) {
            return $existingUser->supervisor;
        }

        // 3. Buat pembimbing baru
        $cleanNip = $trimmedNip ?: null;

        $slugName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode(',', explode(' ', $trimmedName)[0])[0]));
        $baseEmail = ! empty($email)
            ? trim((string) $email)
            : ($slugName . rand(10, 99) . '@dosen.polsub.ac.id');

        $finalEmail = $baseEmail;
        $counter = 1;
        while (User::where('email', $finalEmail)->exists()) {
            $finalEmail = 'dosen' . $counter . '_' . $baseEmail;
            $counter++;
        }

        $user = User::create([
            'name' => $trimmedName,
            'email' => $finalEmail,
            'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(32)),
            'role' => 'pembimbing',
        ]);

        return self::create([
            'user_id' => $user->id,
            'employee_number' => $cleanNip,
            'position' => $position ?: 'Dosen Pembimbing Akademik TRPL',
        ]);
    }
}
