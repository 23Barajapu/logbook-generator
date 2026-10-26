<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityAttachment;
use App\Models\Company;
use App\Models\Internship;
use App\Models\Supervisor;
use App\Services\DocumentService;
use App\Services\DocxImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MahasiswaController extends Controller
{
    public function __construct(
        protected DocumentService $documentService,
        protected DocxImportService $docxImportService
    ) {}

    private function getStudentInternship(): ?Internship
    {
        $student = Auth::user()->student;
        if (! $student) {
            return null;
        }

        return $student->internships()
            ->with(['company', 'supervisor.user', 'evaluation'])
            ->latest()
            ->first();
    }

    public function dashboard(): View
    {
        $internship = $this->getStudentInternship();

        $stats = [
            'total_activities' => 0,
            'approved' => 0,
            'pending' => 0,
            'revision' => 0,
            'draft' => 0,
        ];

        $recentActivities = collect();
        $availableWeeks = [];

        if ($internship) {
            $stats['total_activities'] = $internship->activities()->count();
            $stats['approved'] = $internship->activities()->where('status', 'Disetujui')->count();
            $stats['pending'] = $internship->activities()->where('status', 'Menunggu Approval')->count();
            $stats['revision'] = $internship->activities()->where('status', 'Revisi')->count();
            $stats['draft'] = $internship->activities()->where('status', 'Draft')->count();

            $recentActivities = $internship->activities()
                ->with(['attachments', 'approvals'])
                ->orderBy('day_number', 'desc')
                ->take(5)
                ->get();

            $availableWeeks = $this->documentService->getAvailableWeeks($internship);
        }

        return view('mahasiswa.dashboard', [
            'internship' => $internship,
            'stats' => $stats,
            'recentActivities' => $recentActivities,
            'availableWeeks' => $availableWeeks,
        ]);
    }

    public function activities(Request $request): View
    {
        $internship = $this->getStudentInternship();
        $query = $internship ? $internship->activities()->with(['attachments', 'latestApproval']) : Activity::whereRaw('1=0');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('work_field', 'ilike', "%{$search}%")
                    ->orWhere('activities', 'ilike', "%{$search}%")
                    ->orWhere('tools_used', 'ilike', "%{$search}%");
            });
        }

        $activities = $query->orderBy('day_number', 'desc')->paginate(10)->withQueryString();
        $availableWeeks = $internship ? $this->documentService->getAvailableWeeks($internship) : [];

        return view('mahasiswa.activities.index', [
            'internship' => $internship,
            'activities' => $activities,
            'currentStatus' => $request->status,
            'currentSearch' => $request->search,
            'availableWeeks' => $availableWeeks,
        ]);
    }

    public function createActivity(): View|RedirectResponse
    {
        $internship = $this->getStudentInternship();
        if (! $internship) {
            return redirect()->route('mahasiswa.dashboard')->with('error', 'Data penugasan PKL belum ditemukan.');
        }

        $nextDayNumber = ($internship->activities()->max('day_number') ?? 0) + 1;

        return view('mahasiswa.activities.create', [
            'internship' => $internship,
            'nextDayNumber' => $nextDayNumber,
        ]);
    }

    public function storeActivity(Request $request): RedirectResponse
    {
        $internship = $this->getStudentInternship();
        if (! $internship) {
            return redirect()->route('mahasiswa.dashboard')->with('error', 'Data penugasan PKL belum aktif.');
        }

        $validated = $request->validate([
            'day_number' => ['required', 'integer', 'min:1'],
            'activity_date' => ['required', 'date'],
            'work_field' => ['required', 'string', 'max:150'],
            'activities' => ['required', 'string'],
            'tools_used' => ['nullable', 'string'],
            'problems' => ['nullable', 'string'],
            'solutions' => ['nullable', 'string'],
            'action_type' => ['required', 'in:draft,submit'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf,docx,xlsx', 'max:20480'],
        ], [
            'day_number.required' => 'Nomor hari PKL wajib diisi.',
            'activity_date.required' => 'Tanggal kegiatan wajib diisi.',
            'work_field.required' => 'Bidang atau divisi pekerjaan wajib diisi.',
            'activities.required' => 'Deskripsi kegiatan harian wajib diisi.',
            'attachments.max' => 'Maksimal lampiran adalah 10 file.',
            'attachments.*.max' => 'Ukuran setiap file maksimal 20 MB.',
            'attachments.*.mimes' => 'Format file yang didukung: JPG, JPEG, PNG, PDF, DOCX, XLSX.',
        ]);

        $status = $validated['action_type'] === 'submit' ? 'Menunggu Approval' : 'Draft';

        DB::beginTransaction();
        try {
            $activity = Activity::create([
                'internship_id' => $internship->id,
                'day_number' => $validated['day_number'],
                'activity_date' => $validated['activity_date'],
                'work_field' => $validated['work_field'],
                'activities' => $validated['activities'],
                'tools_used' => $validated['tools_used'],
                'problems' => $validated['problems'],
                'solutions' => $validated['solutions'],
                'status' => $status,
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $originalName = $file->getClientOriginalName();
                    $path = $file->store('attachments/' . $activity->id, 'public');

                    ActivityAttachment::create([
                        'activity_id' => $activity->id,
                        'file_name' => $originalName,
                        'file_path' => $path,
                        'file_type' => $file->getClientOriginalExtension(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            DB::commit();

            $msg = $status === 'Menunggu Approval'
                ? 'Aktivitas berhasil disimpan dan diajukan ke pembimbing.'
                : 'Aktivitas berhasil disimpan sebagai draft.';

            return redirect()->route('mahasiswa.activities')->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Gagal menyimpan aktivitas: ' . $e->getMessage());
        }
    }

    public function showActivity(Activity $activity): View|RedirectResponse
    {
        $internship = $this->getStudentInternship();
        if (! $internship || $activity->internship_id !== $internship->id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $activity->load(['attachments', 'approvals.supervisor.user']);

        return view('mahasiswa.activities.show', [
            'activity' => $activity,
            'internship' => $internship,
        ]);
    }

    public function editActivity(Activity $activity): View|RedirectResponse
    {
        $internship = $this->getStudentInternship();
        if (! $internship || $activity->internship_id !== $internship->id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        if (! in_array($activity->status, ['Draft', 'Revisi'])) {
            return redirect()->route('mahasiswa.activities.show', $activity)
                ->with('error', 'Aktivitas dengan status ' . $activity->status . ' tidak dapat diedit.');
        }

        $activity->load(['attachments', 'approvals']);

        return view('mahasiswa.activities.edit', [
            'activity' => $activity,
            'internship' => $internship,
        ]);
    }

    public function updateActivity(Request $request, Activity $activity): RedirectResponse
    {
        $internship = $this->getStudentInternship();
        if (! $internship || $activity->internship_id !== $internship->id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        if (! in_array($activity->status, ['Draft', 'Revisi'])) {
            return redirect()->route('mahasiswa.activities.show', $activity)
                ->with('error', 'Aktivitas ini sudah tidak dapat diedit.');
        }

        $validated = $request->validate([
            'day_number' => ['required', 'integer', 'min:1'],
            'activity_date' => ['required', 'date'],
            'work_field' => ['required', 'string', 'max:150'],
            'activities' => ['required', 'string'],
            'tools_used' => ['nullable', 'string'],
            'problems' => ['nullable', 'string'],
            'solutions' => ['nullable', 'string'],
            'action_type' => ['required', 'in:draft,submit'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf,docx,xlsx', 'max:20480'],
            'delete_attachments' => ['nullable', 'array'],
            'delete_attachments.*' => ['integer'],
        ]);

        $status = $validated['action_type'] === 'submit' ? 'Menunggu Approval' : 'Draft';

        DB::beginTransaction();
        try {
            $activity->update([
                'day_number' => $validated['day_number'],
                'activity_date' => $validated['activity_date'],
                'work_field' => $validated['work_field'],
                'activities' => $validated['activities'],
                'tools_used' => $validated['tools_used'],
                'problems' => $validated['problems'],
                'solutions' => $validated['solutions'],
                'status' => $status,
            ]);

            // Hapus lampiran yang ditandai
            if ($request->filled('delete_attachments')) {
                $attachmentsToDelete = ActivityAttachment::where('activity_id', $activity->id)
                    ->whereIn('id', $request->delete_attachments)
                    ->get();

                foreach ($attachmentsToDelete as $att) {
                    Storage::disk('public')->delete($att->file_path);
                    $att->delete();
                }
            }

            // Tambah lampiran baru
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $originalName = $file->getClientOriginalName();
                    $path = $file->store('attachments/' . $activity->id, 'public');

                    ActivityAttachment::create([
                        'activity_id' => $activity->id,
                        'file_name' => $originalName,
                        'file_path' => $path,
                        'file_type' => $file->getClientOriginalExtension(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('mahasiswa.activities.show', $activity)
                ->with('success', 'Aktivitas berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Gagal memperbarui aktivitas: ' . $e->getMessage());
        }
    }

    public function submitApproval(Activity $activity): RedirectResponse
    {
        $internship = $this->getStudentInternship();
        if (! $internship || $activity->internship_id !== $internship->id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        if (! in_array($activity->status, ['Draft', 'Revisi'])) {
            return back()->with('error', 'Hanya aktivitas Draft atau Revisi yang dapat diajukan.');
        }

        $activity->update(['status' => 'Menunggu Approval']);

        return back()->with('success', 'Aktivitas berhasil diajukan untuk approval pembimbing.');
    }

    public function destroyActivity(Activity $activity): RedirectResponse
    {
        $internship = $this->getStudentInternship();
        if (! $internship || $activity->internship_id !== $internship->id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        if ($activity->status !== 'Draft') {
            return back()->with('error', 'Hanya aktivitas berstatus Draft yang dapat dihapus.');
        }

        foreach ($activity->attachments as $att) {
            Storage::disk('public')->delete($att->file_path);
            $att->delete();
        }

        $activity->delete();

        return redirect()->route('mahasiswa.activities')->with('success', 'Aktivitas draft berhasil dihapus.');
    }

    public function editInternship(): View
    {
        $student = Auth::user()->student;
        $internship = $this->getStudentInternship();
        $companies = Company::orderBy('company_name', 'asc')->get();
        $supervisors = Supervisor::with('user')->orderBy('id', 'asc')->get();

        return view('mahasiswa.internship.edit', [
            'student' => $student,
            'internship' => $internship,
            'companies' => $companies,
            'supervisors' => $supervisors,
        ]);
    }

    public function updateInternship(Request $request): RedirectResponse
    {
        $student = Auth::user()->student;
        if (! $student) {
            abort(403, 'Profil mahasiswa tidak ditemukan.');
        }

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'field_supervisor' => ['nullable', 'string', 'max:150'],
            'field_supervisor_position' => ['nullable', 'string', 'max:150'],
            'supervisor_id' => ['nullable'],
            'supervisor_name' => ['nullable', 'string', 'max:150'],
            'supervisor_nip' => ['nullable', 'string', 'max:50'],
            'supervisor_position' => ['nullable', 'string', 'max:150'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'campus_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'remove_campus_logo' => ['nullable', 'boolean'],
        ], [
            'company_name.required' => 'Nama instansi / perusahaan PKL wajib diisi.',
            'start_date.required' => 'Tanggal mulai PKL wajib diisi.',
            'end_date.required' => 'Tanggal selesai PKL wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            'campus_logo.image' => 'File logo kampus harus berupa file gambar.',
            'campus_logo.mimes' => 'Format logo kampus harus PNG, JPG, atau JPEG.',
            'campus_logo.max' => 'Ukuran logo kampus maksimal 2MB.',
        ]);

        DB::transaction(function () use ($student, $validated, $request) {
            $company = Company::firstOrCreate(
                ['company_name' => trim($validated['company_name'])],
                [
                    'address' => $validated['company_address'] ?? null,
                    'field_supervisor' => $validated['field_supervisor'] ?? null,
                    'field_supervisor_position' => $validated['field_supervisor_position'] ?? 'Pembimbing Lapangan',
                ]
            );

            $companyUpdateData = [];
            if (! empty($validated['company_address'])) $companyUpdateData['address'] = $validated['company_address'];
            if (! empty($validated['field_supervisor'])) $companyUpdateData['field_supervisor'] = $validated['field_supervisor'];
            if (! empty($validated['field_supervisor_position'])) $companyUpdateData['field_supervisor_position'] = $validated['field_supervisor_position'];
            if (! empty($companyUpdateData)) {
                $company->update($companyUpdateData);
            }

            $supervisor = Supervisor::resolveOrCreate(
                $request->input('supervisor_id'),
                $request->input('supervisor_name'),
                $request->input('supervisor_nip'),
                $request->input('supervisor_position')
            );

            $internship = $this->getStudentInternship();
            $logoPath = $internship?->campus_logo;

            if ($request->boolean('remove_campus_logo')) {
                if ($logoPath && Storage::disk('public')->exists($logoPath)) {
                    Storage::disk('public')->delete($logoPath);
                }
                $logoPath = null;
            }

            if ($request->hasFile('campus_logo')) {
                if ($logoPath && Storage::disk('public')->exists($logoPath)) {
                    Storage::disk('public')->delete($logoPath);
                }
                $logoPath = $request->file('campus_logo')->store('logos', 'public');
            }

            if ($internship) {
                $internship->update([
                    'company_id' => $company->id,
                    'supervisor_id' => $supervisor?->id ?? $internship->supervisor_id,
                    'campus_logo' => $logoPath,
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                ]);
            } else {
                Internship::create([
                    'student_id' => $student->id,
                    'company_id' => $company->id,
                    'supervisor_id' => $supervisor?->id,
                    'campus_logo' => $logoPath,
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'status' => 'aktif',
                ]);
            }
        });

        return redirect()->route('mahasiswa.dashboard')->with('success', 'Data PKL dan Dosen Pembimbing berhasil disimpan.');
    }

    public function showImportForm(): View|RedirectResponse
    {
        $internship = $this->getStudentInternship();
        if (! $internship) {
            return redirect()->route('mahasiswa.dashboard')->with('error', 'Data penugasan PKL belum ditemukan.');
        }

        return view('mahasiswa.activities.import', [
            'internship' => $internship,
        ]);
    }

    public function processImport(Request $request): RedirectResponse
    {
        $internship = $this->getStudentInternship();
        if (! $internship) {
            return redirect()->route('mahasiswa.dashboard')->with('error', 'Data penugasan PKL belum aktif.');
        }

        $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['required', 'file', 'mimes:docx', 'max:25600'],
        ], [
            'files.required' => 'Pilih setidaknya satu file DOCX untuk diunggah.',
            'files.max' => 'Maksimal mengunggah 10 file DOCX sekaligus.',
            'files.*.mimes' => 'Format file wajib berupa dokumen Microsoft Word (.docx).',
            'files.*.max' => 'Ukuran setiap file maksimal 25 MB.',
        ]);

        $result = $this->docxImportService->parseDocxFiles($request->file('files'), $internship->id);

        if (empty($result['activities'])) {
            $this->docxImportService->cancelImport($result['token']);

            return back()->with('error', 'Tidak ada data aktivitas logbook yang dapat diekstrak dari file yang diunggah. Pastikan file menggunakan format tabel logbook PKL (terdapat penanda Hari Ke-X).');
        }

        session(['pending_docx_import' => $result]);

        return redirect()->route('mahasiswa.activities.import.preview');
    }

    public function previewImport(): View|RedirectResponse
    {
        $internship = $this->getStudentInternship();
        if (! $internship) {
            return redirect()->route('mahasiswa.dashboard')->with('error', 'Data penugasan PKL belum aktif.');
        }

        $importData = session('pending_docx_import');
        if (! $importData || empty($importData['activities'])) {
            return redirect()->route('mahasiswa.activities.import')->with('warning', 'Tidak ada sesi impor yang sedang berlangsung.');
        }

        return view('mahasiswa.activities.import-preview', [
            'internship' => $internship,
            'token' => $importData['token'],
            'activities' => $importData['activities'],
            'stats' => $importData['stats'],
        ]);
    }

    public function confirmImport(Request $request): RedirectResponse
    {
        $internship = $this->getStudentInternship();
        if (! $internship) {
            return redirect()->route('mahasiswa.dashboard')->with('error', 'Data penugasan PKL belum aktif.');
        }

        $validated = $request->validate([
            'token' => ['required', 'string'],
            'activities' => ['required', 'array'],
            'activities.*.day_number' => ['required', 'integer', 'min:1'],
            'activities.*.activity_date' => ['required', 'date'],
            'activities.*.work_field' => ['required', 'string', 'max:150'],
            'activities.*.activities' => ['required', 'string'],
            'activities.*.tools_used' => ['nullable', 'string'],
            'activities.*.problems' => ['nullable', 'string'],
            'activities.*.solutions' => ['nullable', 'string'],
            'activities.*.conflict_action' => ['nullable', 'in:overwrite,skip'],
            'activities.*.selected' => ['nullable'],
            'activities.*.images' => ['nullable', 'array'],
            'activities.*.images.*.file_name' => ['nullable', 'string'],
            'activities.*.images.*.temp_path' => ['nullable', 'string'],
            'activities.*.images.*.file_type' => ['nullable', 'string'],
            'activities.*.images.*.file_size' => ['nullable', 'numeric'],
        ]);

        try {
            $stats = $this->docxImportService->saveImportedActivities(
                $internship->id,
                $validated['activities'],
                $validated['token']
            );

            session()->forget('pending_docx_import');

            $message = "Impor selesai: {$stats['saved']} aktivitas baru disimpan";
            if ($stats['updated'] > 0) {
                $message .= ", {$stats['updated']} diperbarui";
            }
            if ($stats['skipped'] > 0) {
                $message .= ", {$stats['skipped']} dilewati";
            }
            $message .= '.';

            return redirect()->route('mahasiswa.activities')->with('success', $message);
        } catch (\Throwable $e) {
            return back()->with('error', 'Terjadi kesalahan saat menyimpan logbook: ' . $e->getMessage());
        }
    }

    public function cancelImport(Request $request): RedirectResponse
    {
        $token = $request->input('token') ?? session('pending_docx_import.token');
        if ($token) {
            $this->docxImportService->cancelImport($token);
        }

        session()->forget('pending_docx_import');

        return redirect()->route('mahasiswa.activities')->with('info', 'Proses impor DOCX telah dibatalkan.');
    }
}
