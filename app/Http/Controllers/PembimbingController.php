<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Approval;
use App\Models\Evaluation;
use App\Models\Internship;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PembimbingController extends Controller
{
    private function getSupervisor()
    {
        return Auth::user()->supervisor;
    }

    public function dashboard(): View
    {
        $supervisor = $this->getSupervisor();

        $internships = $supervisor
            ? $supervisor->internships()->with(['student.user', 'company', 'evaluation'])->get()
            : collect();

        $internshipIds = $internships->pluck('id');

        $pendingActivities = Activity::whereIn('internship_id', $internshipIds)
            ->where('status', 'Menunggu Approval')
            ->with(['internship.student.user'])
            ->orderBy('activity_date', 'asc')
            ->get();

        $stats = [
            'total_students' => $internships->count(),
            'pending_approval' => $pendingActivities->count(),
            'total_evaluated' => $internships->filter(fn($i) => $i->evaluation !== null)->count(),
        ];

        return view('pembimbing.dashboard', [
            'supervisor' => $supervisor,
            'internships' => $internships,
            'pendingActivities' => $pendingActivities,
            'stats' => $stats,
        ]);
    }

    public function students(): View
    {
        $supervisor = $this->getSupervisor();
        $internships = $supervisor
            ? $supervisor->internships()->with(['student.user', 'company', 'evaluation', 'activities'])->get()
            : collect();

        return view('pembimbing.students.index', [
            'internships' => $internships,
        ]);
    }

    public function studentDetail(Internship $internship): View
    {
        $supervisor = $this->getSupervisor();
        if ($internship->supervisor_id !== $supervisor->id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $internship->load([
            'student.user',
            'company',
            'evaluation',
            'activities' => fn($q) => $q->with(['attachments', 'approvals.supervisor.user'])->orderBy('day_number', 'asc'),
        ]);

        return view('pembimbing.students.show', [
            'internship' => $internship,
        ]);
    }

    public function reviewActivity(Activity $activity): View
    {
        $supervisor = $this->getSupervisor();
        $internship = $activity->internship;

        if ($internship->supervisor_id !== $supervisor->id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $activity->load(['attachments', 'approvals.supervisor.user', 'internship.student.user', 'internship.company']);

        return view('pembimbing.activities.review', [
            'activity' => $activity,
            'internship' => $internship,
        ]);
    }

    public function processApproval(Request $request, Activity $activity): RedirectResponse
    {
        $supervisor = $this->getSupervisor();
        $internship = $activity->internship;

        if ($internship->supervisor_id !== $supervisor->id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $validated = $request->validate([
            'status' => ['required', 'in:Disetujui,Revisi'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'status.required' => 'Pilihan status persetujuan wajib ditentukan.',
            'notes.max' => 'Catatan maksimal 1000 karakter.',
        ]);

        DB::beginTransaction();
        try {
            $activity->update([
                'status' => $validated['status'],
            ]);

            Approval::create([
                'activity_id' => $activity->id,
                'supervisor_id' => $supervisor->id,
                'notes' => $validated['notes'],
                'status' => $validated['status'],
                'approved_at' => now(),
            ]);

            DB::commit();

            $msg = $validated['status'] === 'Disetujui'
                ? 'Aktivitas hari ke-' . $activity->day_number . ' telah disetujui.'
                : 'Permintaan revisi aktivitas hari ke-' . $activity->day_number . ' telah dikirimkan ke mahasiswa.';

            return redirect()->route('pembimbing.students.show', $internship)->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal memproses approval: ' . $e->getMessage());
        }
    }

    public function evaluateForm(Internship $internship): View
    {
        $supervisor = $this->getSupervisor();
        if ($internship->supervisor_id !== $supervisor->id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $internship->load(['student.user', 'company', 'evaluation']);

        return view('pembimbing.students.evaluate', [
            'internship' => $internship,
            'evaluation' => $internship->evaluation,
        ]);
    }

    public function saveEvaluation(Request $request, Internship $internship): RedirectResponse
    {
        $supervisor = $this->getSupervisor();
        if ($internship->supervisor_id !== $supervisor->id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $validated = $request->validate([
            'attendance_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'discipline_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'activity_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'report_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'attendance_score.required' => 'Nilai kehadiran (bobot 20%) wajib diisi.',
            'discipline_score.required' => 'Nilai disiplin (bobot 20%) wajib diisi.',
            'activity_score.required' => 'Nilai aktivitas harian (bobot 30%) wajib diisi.',
            'report_score.required' => 'Nilai laporan PKL (bobot 30%) wajib diisi.',
        ]);

        $finalScore = Evaluation::calculateFinalScore(
            (float) $validated['attendance_score'],
            (float) $validated['discipline_score'],
            (float) $validated['activity_score'],
            (float) $validated['report_score']
        );

        Evaluation::updateOrCreate(
            ['internship_id' => $internship->id],
            [
                'supervisor_id' => $supervisor->id,
                'attendance_score' => $validated['attendance_score'],
                'discipline_score' => $validated['discipline_score'],
                'activity_score' => $validated['activity_score'],
                'report_score' => $validated['report_score'],
                'final_score' => $finalScore,
                'notes' => $validated['notes'],
            ]
        );

        return redirect()->route('pembimbing.students.show', $internship)
            ->with('success', 'Penilaian PKL berhasil disimpan dengan nilai akhir: ' . $finalScore);
    }
}
