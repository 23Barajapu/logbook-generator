<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Company;
use App\Models\Internship;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MahasiswaTest extends TestCase
{
    use RefreshDatabase;

    private function createStudentSetup()
    {
        $user = User::create([
            'name' => 'Budi Mahasiswa',
            'email' => 'budi@test.com',
            'password' => Hash::make('password'),
            'role' => 'mahasiswa',
        ]);

        $student = Student::create([
            'user_id' => $user->id,
            'nim' => '22010101',
            'study_program' => 'Informatika',
            'batch_year' => 2022,
        ]);

        $company = Company::create([
            'company_name' => 'PT Mitra Solusi',
            'address' => 'Jakarta',
            'field_supervisor' => 'Pak Joko',
        ]);

        $spvUser = User::create([
            'name' => 'Dosen Pembimbing',
            'email' => 'dosen@test.com',
            'password' => Hash::make('password'),
            'role' => 'pembimbing',
        ]);

        $supervisor = Supervisor::create([
            'user_id' => $spvUser->id,
            'employee_number' => '198001012010011001',
        ]);

        $internship = Internship::create([
            'student_id' => $student->id,
            'company_id' => $company->id,
            'supervisor_id' => $supervisor->id,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(80)->toDateString(),
            'status' => 'aktif',
        ]);

        return compact('user', 'student', 'company', 'supervisor', 'internship');
    }

    public function test_mahasiswa_can_view_dashboard(): void
    {
        $setup = $this->createStudentSetup();

        $response = $this->actingAs($setup['user'])->get(route('mahasiswa.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Budi Mahasiswa');
        $response->assertSee('PT Mitra Solusi');
    }

    public function test_mahasiswa_can_create_activity_as_draft(): void
    {
        $setup = $this->createStudentSetup();
        Storage::fake('public');

        $file = UploadedFile::fake()->create('bukti.pdf', 500, 'application/pdf');

        $response = $this->actingAs($setup['user'])->post(route('mahasiswa.activities.store'), [
            'day_number' => 1,
            'activity_date' => now()->toDateString(),
            'work_field' => 'Backend Development',
            'activities' => 'Mempelajari struktur database dan membuat API endpoint baru.',
            'tools_used' => 'VS Code, PostgreSQL, Postman',
            'problems' => 'Tidak ada kendala',
            'solutions' => 'Lancar',
            'action_type' => 'draft',
            'attachments' => [$file],
        ]);

        $response->assertRedirect(route('mahasiswa.activities'));
        $this->assertDatabaseHas('activities', [
            'internship_id' => $setup['internship']->id,
            'day_number' => 1,
            'status' => 'Draft',
        ]);
        $this->assertDatabaseHas('activity_attachments', [
            'file_name' => 'bukti.pdf',
        ]);
    }

    public function test_mahasiswa_can_submit_activity_for_approval(): void
    {
        $setup = $this->createStudentSetup();

        $activity = Activity::create([
            'internship_id' => $setup['internship']->id,
            'day_number' => 1,
            'activity_date' => now()->toDateString(),
            'work_field' => 'Frontend',
            'activities' => 'Implementasi halaman login responsif.',
            'status' => 'Draft',
        ]);

        $response = $this->actingAs($setup['user'])
            ->post(route('mahasiswa.activities.submit', $activity));

        $response->assertRedirect();
        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'status' => 'Menunggu Approval',
        ]);
    }

    public function test_mahasiswa_cannot_access_admin_or_pembimbing_routes(): void
    {
        $setup = $this->createStudentSetup();

        $responseAdmin = $this->actingAs($setup['user'])->get('/admin/dashboard');
        $responseAdmin->assertStatus(403);

        $responseSpv = $this->actingAs($setup['user'])->get('/pembimbing/dashboard');
        $responseSpv->assertStatus(403);
    }

    public function test_mahasiswa_can_view_and_update_internship_and_supervisor(): void
    {
        $setup = $this->createStudentSetup();

        // 1. Can view edit form
        $response = $this->actingAs($setup['user'])->get(route('mahasiswa.internship.edit'));
        $response->assertStatus(200);
        $response->assertSee('Data PKL & Dosen Pembimbing');

        // 2. Can update with newly inputted supervisor
        $payload = [
            'company_name' => 'PT Transformasi Teknologi Baru',
            'company_address' => 'Jl. Sudirman No. 50, Jakarta',
            'field_supervisor' => 'Denny Pratama',
            'field_supervisor_position' => 'Engineering Manager',
            'supervisor_name' => 'Dr. Maya Kartika, M.T.',
            'supervisor_nip' => '198501012010122001',
            'start_date' => '2026-10-01',
            'end_date' => '2026-12-31',
        ];

        $responseUpdate = $this->actingAs($setup['user'])
            ->put(route('mahasiswa.internship.update'), $payload);

        $responseUpdate->assertRedirect(route('mahasiswa.dashboard'));

        $this->assertDatabaseHas('companies', [
            'company_name' => 'PT Transformasi Teknologi Baru',
            'field_supervisor' => 'Denny Pratama',
        ]);

        $this->assertDatabaseHas('supervisors', [
            'employee_number' => '198501012010122001',
        ]);

        $newSupervisor = \App\Models\Supervisor::where('employee_number', '198501012010122001')->first();
        $this->assertNotNull($newSupervisor);

        $this->assertDatabaseHas('internships', [
            'id' => $setup['internship']->id,
            'supervisor_id' => $newSupervisor->id,
        ]);
    }

    public function test_mahasiswa_can_upload_and_remove_campus_logo(): void
    {
        Storage::fake('public');
        $setup = $this->createStudentSetup();

        // 1. Form menampilkan field logo kampus
        $response = $this->actingAs($setup['user'])->get(route('mahasiswa.internship.edit'));
        $response->assertStatus(200);
        $response->assertSee('Logo Kampus untuk Sampul Logbook');

        // 2. Upload logo kampus
        $file = UploadedFile::fake()->image('logo_kampus.png', 150, 150);
        $payload = [
            'company_name' => $setup['company']->company_name,
            'company_address' => $setup['company']->address,
            'start_date' => '2026-10-01',
            'end_date' => '2026-12-31',
            'campus_logo' => $file,
        ];

        $responseUpload = $this->actingAs($setup['user'])
            ->put(route('mahasiswa.internship.update'), $payload);

        $responseUpload->assertRedirect(route('mahasiswa.dashboard'));

        $setup['internship']->refresh();
        $this->assertNotNull($setup['internship']->campus_logo);
        Storage::disk('public')->assertExists($setup['internship']->campus_logo);

        // 3. Form sekarang menampilkan status logo kustom terpasang
        $responseEditAgain = $this->actingAs($setup['user'])->get(route('mahasiswa.internship.edit'));
        $responseEditAgain->assertStatus(200);
        $responseEditAgain->assertSee('Logo Kustom Terpasang');

        // 4. Hapus logo kustom (reset ke default)
        $payloadRemove = [
            'company_name' => $setup['company']->company_name,
            'company_address' => $setup['company']->address,
            'start_date' => '2026-10-01',
            'end_date' => '2026-12-31',
            'remove_campus_logo' => 1,
        ];

        $responseRemove = $this->actingAs($setup['user'])
            ->put(route('mahasiswa.internship.update'), $payloadRemove);

        $responseRemove->assertRedirect(route('mahasiswa.dashboard'));

        $setup['internship']->refresh();
        $this->assertNull($setup['internship']->campus_logo);
    }
}
