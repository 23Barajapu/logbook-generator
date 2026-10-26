<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Company;
use App\Models\Evaluation;
use App\Models\Internship;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PembimbingTest extends TestCase
{
    use RefreshDatabase;

    private function createSupervisorSetup()
    {
        $spvUser = User::create([
            'name' => 'Dr. Budi Dosen',
            'email' => 'budi_spv@test.com',
            'password' => Hash::make('password'),
            'role' => 'pembimbing',
        ]);

        $supervisor = Supervisor::create([
            'user_id' => $spvUser->id,
            'employee_number' => '198501012015011002',
            'position' => 'Dosen Pembimbing',
        ]);

        $mhsUser = User::create([
            'name' => 'Siswa Bimbingan',
            'email' => 'siswa@test.com',
            'password' => Hash::make('password'),
            'role' => 'mahasiswa',
        ]);

        $student = Student::create([
            'user_id' => $mhsUser->id,
            'nim' => '22019999',
            'study_program' => 'Sistem Informasi',
        ]);

        $company = Company::create([
            'company_name' => 'PT Solusi Siber Mandiri',
            'field_supervisor' => 'Pak Agus',
        ]);

        $internship = Internship::create([
            'student_id' => $student->id,
            'company_id' => $company->id,
            'supervisor_id' => $supervisor->id,
            'start_date' => now()->subDays(15)->toDateString(),
            'end_date' => now()->addDays(75)->toDateString(),
            'status' => 'aktif',
        ]);

        $activity = Activity::create([
            'internship_id' => $internship->id,
            'day_number' => 1,
            'activity_date' => now()->subDays(5)->toDateString(),
            'work_field' => 'Backend',
            'activities' => 'Setup server and postgres database connection.',
            'status' => 'Menunggu Approval',
        ]);

        return compact('spvUser', 'supervisor', 'student', 'company', 'internship', 'activity');
    }

    public function test_pembimbing_can_view_dashboard(): void
    {
        $setup = $this->createSupervisorSetup();

        $response = $this->actingAs($setup['spvUser'])->get(route('pembimbing.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Siswa Bimbingan');
    }

    public function test_pembimbing_can_approve_activity(): void
    {
        $setup = $this->createSupervisorSetup();

        $response = $this->actingAs($setup['spvUser'])->post(route('pembimbing.activities.approval', $setup['activity']), [
            'status' => 'Disetujui',
            'notes' => 'Aktivitas valid dan telah diverifikasi dengan baik.',
        ]);

        $response->assertRedirect(route('pembimbing.students.show', $setup['internship']));
        $this->assertDatabaseHas('activities', [
            'id' => $setup['activity']->id,
            'status' => 'Disetujui',
        ]);
        $this->assertDatabaseHas('approvals', [
            'activity_id' => $setup['activity']->id,
            'supervisor_id' => $setup['supervisor']->id,
            'status' => 'Disetujui',
            'notes' => 'Aktivitas valid dan telah diverifikasi dengan baik.',
        ]);
    }

    public function test_pembimbing_can_request_revision_with_notes(): void
    {
        $setup = $this->createSupervisorSetup();

        $response = $this->actingAs($setup['spvUser'])->post(route('pembimbing.activities.approval', $setup['activity']), [
            'status' => 'Revisi',
            'notes' => 'Tolong lampirkan bukti screenshot skema database yang dibuat.',
        ]);

        $response->assertRedirect(route('pembimbing.students.show', $setup['internship']));
        $this->assertDatabaseHas('activities', [
            'id' => $setup['activity']->id,
            'status' => 'Revisi',
        ]);
        $this->assertDatabaseHas('approvals', [
            'activity_id' => $setup['activity']->id,
            'status' => 'Revisi',
        ]);
    }

    public function test_pembimbing_can_evaluate_internship_with_correct_formula(): void
    {
        $setup = $this->createSupervisorSetup();

        // Formula: 80*0.2 + 90*0.2 + 85*0.3 + 95*0.3 = 16 + 18 + 25.5 + 28.5 = 88.00
        $response = $this->actingAs($setup['spvUser'])->post(route('pembimbing.students.evaluate.save', $setup['internship']), [
            'attendance_score' => 80,
            'discipline_score' => 90,
            'activity_score' => 85,
            'report_score' => 95,
            'notes' => 'Kinerja magang sangat memuaskan.',
        ]);

        $response->assertRedirect(route('pembimbing.students.show', $setup['internship']));
        $this->assertDatabaseHas('evaluations', [
            'internship_id' => $setup['internship']->id,
            'attendance_score' => 80,
            'discipline_score' => 90,
            'activity_score' => 85,
            'report_score' => 95,
            'final_score' => 88.00,
        ]);
    }
}
