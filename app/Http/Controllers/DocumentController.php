<?php

namespace App\Http\Controllers;

use App\Models\Internship;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    public function __construct(
        protected DocumentService $documentService
    ) {}

    private function authorizeInternshipAccess(Internship $internship): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->isAdmin()) {
            return;
        }

        if ($user->isSupervisor()) {
            if ($internship->supervisor_id !== $user->supervisor?->id) {
                abort(403, 'Akses ke data PKL ini tidak diizinkan.');
            }
            return;
        }

        if ($user->isStudent()) {
            if ($internship->student_id !== $user->student?->id) {
                abort(403, 'Akses ke data PKL ini tidak diizinkan.');
            }
            return;
        }

        abort(403, 'Peran tidak dikenali.');
    }

    public function downloadPdf(Request $request, Internship $internship): Response
    {
        $this->authorizeInternshipAccess($internship);

        @ini_set('memory_limit', '1024M');
        @set_time_limit(300);

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $week = $request->query('week');

        $pdf = $this->documentService->generatePdf($internship, $startDate, $endDate);

        $nim = $internship->student->nim ?? 'mahasiswa';
        $suffix = $week ? '_Minggu_' . $week : (($startDate && $endDate) ? '_' . $startDate . '_' . $endDate : '_Keseluruhan');
        $fileName = 'Logbook_PKL_' . $nim . $suffix . '.pdf';

        return $pdf->download($fileName);
    }

    public function downloadDocx(Request $request, Internship $internship): BinaryFileResponse
    {
        $this->authorizeInternshipAccess($internship);

        @ini_set('memory_limit', '1024M');
        @set_time_limit(300);

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $week = $request->query('week');

        $filePath = $this->documentService->generateDocx($internship, $startDate, $endDate);

        $nim = $internship->student->nim ?? 'mahasiswa';
        $suffix = $week ? '_Minggu_' . $week : (($startDate && $endDate) ? '_' . $startDate . '_' . $endDate : '_Keseluruhan');
        $fileName = 'Logbook_PKL_' . $nim . $suffix . '.docx';

        return response()->download($filePath, $fileName)->deleteFileAfterSend(true);
    }

    public function downloadTemplate(): BinaryFileResponse
    {
        $filePath = $this->documentService->generateTemplateDocx();
        $fileName = basename($filePath);

        return response()->download($filePath, $fileName);
    }

    public function summary(Internship $internship): View
    {
        $this->authorizeInternshipAccess($internship);

        $internship->load([
            'student.user',
            'company',
            'supervisor.user',
            'evaluation',
            'activities' => fn($q) => $q->orderBy('day_number', 'asc')->with('approvals'),
        ]);

        $activities = $internship->activities;

        $totalDays = $activities->count();
        $approvedCount = $activities->where('status', 'Disetujui')->count();
        $pendingCount = $activities->where('status', 'Menunggu Approval')->count();
        $revisionCount = $activities->where('status', 'Revisi')->count();
        $draftCount = $activities->where('status', 'Draft')->count();

        // Statistik bidang kerja
        $workFieldStats = $activities->groupBy('work_field')
            ->map(fn($group) => $group->count())
            ->sortDesc();

        // Statistik tools
        $toolsList = [];
        foreach ($activities as $act) {
            if (! empty($act->tools_used)) {
                $tools = array_map('trim', explode(',', $act->tools_used));
                foreach ($tools as $t) {
                    if ($t !== '') {
                        $toolsList[$t] = ($toolsList[$t] ?? 0) + 1;
                    }
                }
            }
        }
        arsort($toolsList);

        return view('documents.summary', [
            'internship' => $internship,
            'totalDays' => $totalDays,
            'approvedCount' => $approvedCount,
            'pendingCount' => $pendingCount,
            'revisionCount' => $revisionCount,
            'draftCount' => $draftCount,
            'workFieldStats' => $workFieldStats,
            'toolsStats' => array_slice($toolsList, 0, 10, true),
        ]);
    }
}
