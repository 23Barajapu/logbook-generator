<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Internship;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Admin Sistem',
            'email' => 'admin_test@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
    }

    public function test_admin_can_view_dashboard(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Dashboard Administrator');
    }

    public function test_admin_can_create_student(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.students.store'), [
            'name' => 'Mahasiswa Baru',
            'email' => 'mhsbaru@test.com',
            'password' => 'password123',
            'nim' => '22099999',
            'study_program' => 'Teknik Elektro',
            'batch_year' => 2022,
            'phone_number' => '0812999999',
        ]);

        $response->assertRedirect(route('admin.students'));
        $this->assertDatabaseHas('students', ['nim' => '22099999']);
        $this->assertDatabaseHas('users', ['email' => 'mhsbaru@test.com', 'role' => 'mahasiswa']);
    }

    public function test_admin_can_create_supervisor(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.supervisors.store'), [
            'name' => 'Prof. Dosen Baru',
            'email' => 'dosenbaru@test.com',
            'password' => 'password123',
            'employee_number' => '197501012000011001',
            'position' => 'Guru Besar',
        ]);

        $response->assertRedirect(route('admin.supervisors'));
        $this->assertDatabaseHas('supervisors', ['employee_number' => '197501012000011001']);
        $this->assertDatabaseHas('users', ['email' => 'dosenbaru@test.com', 'role' => 'pembimbing']);
    }

    public function test_admin_can_create_company(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.companies.store'), [
            'company_name' => 'PT Mega Cyber Tech',
            'address' => 'Jl. Mega Kuningan Barat',
            'field_supervisor' => 'Ir. Hendri',
            'field_supervisor_position' => 'CTO',
        ]);

        $response->assertRedirect(route('admin.companies'));
        $this->assertDatabaseHas('companies', ['company_name' => 'PT Mega Cyber Tech']);
    }

    public function test_admin_can_assign_internship(): void
    {
        $admin = $this->createAdmin();

        $user = User::create([
            'name' => 'Mhs Assign',
            'email' => 'assign@test.com',
            'password' => Hash::make('password'),
            'role' => 'mahasiswa',
        ]);
        $student = Student::create([
            'user_id' => $user->id,
            'nim' => '22088888',
            'study_program' => 'Informatika',
        ]);

        $company = Company::create(['company_name' => 'PT Industri Solusi']);

        $response = $this->actingAs($admin)->post(route('admin.internships.store'), [
            'student_id' => $student->id,
            'company_id' => $company->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(60)->toDateString(),
            'status' => 'aktif',
        ]);

        $response->assertRedirect(route('admin.internships'));
        $this->assertDatabaseHas('internships', [
            'student_id' => $student->id,
            'company_id' => $company->id,
            'status' => 'aktif',
        ]);
    }
}
