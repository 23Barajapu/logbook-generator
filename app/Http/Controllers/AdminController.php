<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Company;
use App\Models\Internship;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        $stats = [
            'total_students' => Student::count(),
            'total_supervisors' => Supervisor::count(),
            'total_companies' => Company::count(),
            'active_internships' => Internship::where('status', 'aktif')->count(),
            'total_activities' => Activity::count(),
        ];

        $recentInternships = Internship::with(['student.user', 'company', 'supervisor.user'])
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'recentInternships' => $recentInternships,
        ]);
    }

    // 1. Kelola Mahasiswa
    public function students(): View
    {
        $students = Student::with('user', 'internships.company')->latest()->paginate(15);

        return view('admin.students.index', ['students' => $students]);
    }

    public function createStudent(): View
    {
        return view('admin.students.create');
    }

    public function storeStudent(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'nim' => ['required', 'string', 'max:50', 'unique:students,nim'],
            'study_program' => ['required', 'string', 'max:150'],
            'batch_year' => ['nullable', 'integer'],
            'phone_number' => ['nullable', 'string', 'max:30'],
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'mahasiswa',
            ]);

            Student::create([
                'user_id' => $user->id,
                'nim' => $validated['nim'],
                'study_program' => $validated['study_program'],
                'batch_year' => $validated['batch_year'],
                'phone_number' => $validated['phone_number'],
            ]);
        });

        return redirect()->route('admin.students')->with('success', 'Mahasiswa berhasil ditambahkan.');
    }

    public function editStudent(Student $student): View
    {
        $student->load('user');

        return view('admin.students.edit', ['student' => $student]);
    }

    public function updateStudent(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'unique:users,email,' . $student->user_id],
            'password' => ['nullable', 'string', 'min:6'],
            'nim' => ['required', 'string', 'max:50', 'unique:students,nim,' . $student->id],
            'study_program' => ['required', 'string', 'max:150'],
            'batch_year' => ['nullable', 'integer'],
            'phone_number' => ['nullable', 'string', 'max:30'],
        ]);

        DB::transaction(function () use ($student, $validated) {
            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
            ];
            if (! empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
            }
            $student->user->update($userData);

            $student->update([
                'nim' => $validated['nim'],
                'study_program' => $validated['study_program'],
                'batch_year' => $validated['batch_year'],
                'phone_number' => $validated['phone_number'],
            ]);
        });

        return redirect()->route('admin.students')->with('success', 'Data mahasiswa berhasil diperbarui.');
    }

    public function destroyStudent(Student $student): RedirectResponse
    {
        DB::transaction(function () use ($student) {
            $user = $student->user;
            $student->delete();
            $user?->delete();
        });

        return redirect()->route('admin.students')->with('success', 'Mahasiswa berhasil dihapus.');
    }

    // 2. Kelola Pembimbing
    public function supervisors(): View
    {
        $supervisors = Supervisor::with('user', 'internships')->latest()->paginate(15);

        return view('admin.supervisors.index', ['supervisors' => $supervisors]);
    }

    public function createSupervisor(): View
    {
        return view('admin.supervisors.create');
    }

    public function storeSupervisor(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'employee_number' => ['nullable', 'string', 'max:50', 'unique:supervisors,employee_number'],
            'position' => ['nullable', 'string', 'max:150'],
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'pembimbing',
            ]);

            Supervisor::create([
                'user_id' => $user->id,
                'employee_number' => $validated['employee_number'] ?: null,
                'position' => $validated['position'],
            ]);
        });

        return redirect()->route('admin.supervisors')->with('success', 'Pembimbing berhasil ditambahkan.');
    }

    public function editSupervisor(Supervisor $supervisor): View
    {
        $supervisor->load('user');

        return view('admin.supervisors.edit', ['supervisor' => $supervisor]);
    }

    public function updateSupervisor(Request $request, Supervisor $supervisor): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'unique:users,email,' . $supervisor->user_id],
            'password' => ['nullable', 'string', 'min:6'],
            'employee_number' => ['nullable', 'string', 'max:50', 'unique:supervisors,employee_number,' . $supervisor->id],
            'position' => ['nullable', 'string', 'max:150'],
        ]);

        DB::transaction(function () use ($supervisor, $validated) {
            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
            ];
            if (! empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
            }
            $supervisor->user->update($userData);

            $supervisor->update([
                'employee_number' => $validated['employee_number'] ?: null,
                'position' => $validated['position'],
            ]);
        });

        return redirect()->route('admin.supervisors')->with('success', 'Data pembimbing berhasil diperbarui.');
    }

    public function destroySupervisor(Supervisor $supervisor): RedirectResponse
    {
        DB::transaction(function () use ($supervisor) {
            $user = $supervisor->user;
            $supervisor->delete();
            $user?->delete();
        });

        return redirect()->route('admin.supervisors')->with('success', 'Pembimbing berhasil dihapus.');
    }

    // 3. Kelola Perusahaan
    public function companies(): View
    {
        $companies = Company::withCount('internships')->latest()->paginate(15);

        return view('admin.companies.index', ['companies' => $companies]);
    }

    public function createCompany(): View
    {
        return view('admin.companies.create');
    }

    public function storeCompany(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'field_supervisor' => ['nullable', 'string', 'max:150'],
            'field_supervisor_position' => ['nullable', 'string', 'max:150'],
        ]);

        Company::create($validated);

        return redirect()->route('admin.companies')->with('success', 'Data perusahaan berhasil disimpan.');
    }

    public function editCompany(Company $company): View
    {
        return view('admin.companies.edit', ['company' => $company]);
    }

    public function updateCompany(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'field_supervisor' => ['nullable', 'string', 'max:150'],
            'field_supervisor_position' => ['nullable', 'string', 'max:150'],
        ]);

        $company->update($validated);

        return redirect()->route('admin.companies')->with('success', 'Data perusahaan berhasil diperbarui.');
    }

    public function destroyCompany(Company $company): RedirectResponse
    {
        $company->delete();

        return redirect()->route('admin.companies')->with('success', 'Data perusahaan berhasil dihapus.');
    }

    // 4. Kelola Penugasan PKL (Internships)
    public function internships(): View
    {
        $internships = Internship::with(['student.user', 'company', 'supervisor.user'])
            ->latest()
            ->paginate(15);

        return view('admin.internships.index', ['internships' => $internships]);
    }

    public function createInternship(): View
    {
        $students = Student::with('user')->get();
        $companies = Company::all();
        $supervisors = Supervisor::with('user')->get();

        return view('admin.internships.create', [
            'students' => $students,
            'companies' => $companies,
            'supervisors' => $supervisors,
        ]);
    }

    public function storeInternship(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'company_id' => ['required', 'exists:companies,id'],
            'supervisor_id' => ['nullable', 'exists:supervisors,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:aktif,selesai,nonaktif'],
            'campus_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ], [
            'campus_logo.image' => 'File logo kampus harus berupa file gambar.',
            'campus_logo.mimes' => 'Format logo kampus harus PNG, JPG, atau JPEG.',
            'campus_logo.max' => 'Ukuran logo kampus maksimal 2MB.',
        ]);

        if ($request->hasFile('campus_logo')) {
            $validated['campus_logo'] = $request->file('campus_logo')->store('logos', 'public');
        }

        Internship::create($validated);

        return redirect()->route('admin.internships')->with('success', 'Penugasan PKL berhasil dibuat.');
    }

    public function editInternship(Internship $internship): View
    {
        $students = Student::with('user')->get();
        $companies = Company::all();
        $supervisors = Supervisor::with('user')->get();

        return view('admin.internships.edit', [
            'internship' => $internship,
            'students' => $students,
            'companies' => $companies,
            'supervisors' => $supervisors,
        ]);
    }

    public function updateInternship(Request $request, Internship $internship): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'company_id' => ['required', 'exists:companies,id'],
            'supervisor_id' => ['nullable', 'exists:supervisors,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:aktif,selesai,nonaktif'],
            'campus_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'remove_campus_logo' => ['nullable', 'boolean'],
        ], [
            'campus_logo.image' => 'File logo kampus harus berupa file gambar.',
            'campus_logo.mimes' => 'Format logo kampus harus PNG, JPG, atau JPEG.',
            'campus_logo.max' => 'Ukuran logo kampus maksimal 2MB.',
        ]);

        if ($request->boolean('remove_campus_logo')) {
            if ($internship->campus_logo && Storage::disk('public')->exists($internship->campus_logo)) {
                Storage::disk('public')->delete($internship->campus_logo);
            }
            $validated['campus_logo'] = null;
        } elseif ($request->hasFile('campus_logo')) {
            if ($internship->campus_logo && Storage::disk('public')->exists($internship->campus_logo)) {
                Storage::disk('public')->delete($internship->campus_logo);
            }
            $validated['campus_logo'] = $request->file('campus_logo')->store('logos', 'public');
        } else {
            unset($validated['campus_logo']);
        }

        unset($validated['remove_campus_logo']);

        $internship->update($validated);

        return redirect()->route('admin.internships')->with('success', 'Penugasan PKL berhasil diperbarui.');
    }

    public function destroyInternship(Internship $internship): RedirectResponse
    {
        if ($internship->campus_logo && Storage::disk('public')->exists($internship->campus_logo)) {
            Storage::disk('public')->delete($internship->campus_logo);
        }

        $internship->delete();

        return redirect()->route('admin.internships')->with('success', 'Penugasan PKL berhasil dihapus.');
    }
}
