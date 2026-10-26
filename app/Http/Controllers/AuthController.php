<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Internship;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        return view('auth.login');
    }

    public function showRegister(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        $companies = Company::orderBy('company_name', 'asc')->get();
        $supervisors = Supervisor::with('user')->orderBy('id', 'asc')->get();

        return view('auth.register', [
            'companies' => $companies,
            'supervisors' => $supervisors,
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'nim' => ['required', 'string', 'max:50', 'unique:students,nim'],
            'study_program' => ['required', 'string', 'max:150'],
            'batch_year' => ['required', 'integer', 'min:2000', 'max:2099'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'field_supervisor' => ['nullable', 'string', 'max:150'],
            'field_supervisor_position' => ['nullable', 'string', 'max:150'],
            'supervisor_id' => ['nullable'],
            'supervisor_name' => ['nullable', 'string', 'max:150'],
            'supervisor_nip' => ['nullable', 'string', 'max:50'],
            'supervisor_position' => ['nullable', 'string', 'max:150'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar. Silakan login atau gunakan email lain.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'nim.required' => 'NIM wajib diisi.',
            'nim.unique' => 'NIM ini sudah terdaftar di sistem.',
            'study_program.required' => 'Program studi wajib dipilih atau diisi.',
            'batch_year.required' => 'Tahun angkatan wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
        ]);

        $user = DB::transaction(function () use ($validated, $request) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'mahasiswa',
            ]);

            $student = Student::create([
                'user_id' => $user->id,
                'nim' => $validated['nim'],
                'study_program' => $validated['study_program'],
                'batch_year' => $validated['batch_year'],
                'phone_number' => $validated['phone_number'] ?? null,
            ]);

            // Dapatkan atau buat dosen pembimbing jika dipilih/diinput mahasiswa
            $supervisor = Supervisor::resolveOrCreate(
                $request->input('supervisor_id'),
                $request->input('supervisor_name'),
                $request->input('supervisor_nip'),
                $request->input('supervisor_position')
            );

            // Jika mengisikan tempat PKL atau dosen pembimbing, buatkan Internship aktif langsung
            $companyName = ! empty($validated['company_name']) ? trim($validated['company_name']) : null;
            if ($companyName || $supervisor) {
                $company = Company::firstOrCreate(
                    ['company_name' => $companyName ?: 'Instansi / Perusahaan PKL'],
                    [
                        'address' => $validated['company_address'] ?? null,
                        'field_supervisor' => $validated['field_supervisor'] ?? null,
                        'field_supervisor_position' => $validated['field_supervisor_position'] ?? 'Pembimbing Lapangan',
                    ]
                );

                $startDate = ! empty($validated['start_date']) ? $validated['start_date'] : now()->toDateString();
                $endDate = ! empty($validated['end_date']) ? $validated['end_date'] : now()->addMonths(3)->toDateString();

                Internship::create([
                    'student_id' => $student->id,
                    'company_id' => $company->id,
                    'supervisor_id' => $supervisor?->id,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => 'aktif',
                ]);
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('mahasiswa.dashboard')->with('success', 'Akun mahasiswa Anda berhasil didaftarkan. Selamat datang!');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return $this->redirectByRole(Auth::user()->role);
        }

        return back()->withErrors([
            'email' => 'Kombinasi email dan password tidak sesuai.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar sistem.');
    }

    private function redirectByRole(string $role): RedirectResponse
    {
        return match ($role) {
            'admin' => redirect()->route('admin.dashboard'),
            'pembimbing' => redirect()->route('pembimbing.dashboard'),
            default => redirect()->route('mahasiswa.dashboard'),
        };
    }
}
