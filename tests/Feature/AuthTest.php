<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Logbook Generator');
    }

    public function test_mahasiswa_can_authenticate_and_redirect_to_dashboard(): void
    {
        $user = User::create([
            'name' => 'Mahasiswa Test',
            'email' => 'mhs@test.com',
            'password' => Hash::make('password'),
            'role' => 'mahasiswa',
        ]);

        $response = $this->post('/login', [
            'email' => 'mhs@test.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('mahasiswa.dashboard'));
    }

    public function test_pembimbing_can_authenticate_and_redirect_to_dashboard(): void
    {
        $user = User::create([
            'name' => 'Pembimbing Test',
            'email' => 'spv@test.com',
            'password' => Hash::make('password'),
            'role' => 'pembimbing',
        ]);

        $response = $this->post('/login', [
            'email' => 'spv@test.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('pembimbing.dashboard'));
    }

    public function test_admin_can_authenticate_and_redirect_to_dashboard(): void
    {
        $user = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        User::create([
            'name' => 'User Test',
            'email' => 'user@test.com',
            'password' => Hash::make('password'),
            'role' => 'mahasiswa',
        ]);

        $this->post('/login', [
            'email' => 'user@test.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::create([
            'name' => 'User Test',
            'email' => 'logout@test.com',
            'password' => Hash::make('password'),
            'role' => 'mahasiswa',
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Pendaftaran Mahasiswa');
    }

    public function test_new_students_can_register_and_redirect_to_dashboard(): void
    {
        $payload = [
            'name' => 'Fajar Nugraha',
            'email' => 'fajar.nugraha@univ.ac.id',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'nim' => '210102099',
            'study_program' => 'D4 Rekayasa Perangkat Lunak',
            'batch_year' => 2022,
            'phone_number' => '081234567890',
            'company_name' => 'PT Solusi Teknologi Indonesia',
            'company_address' => 'Jl. Asia Afrika No. 10, Bandung',
            'field_supervisor' => 'Ir. Hendri Santoso',
            'field_supervisor_position' => 'Tech Lead',
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-31',
        ];

        $response = $this->post('/register', $payload);

        $response->assertRedirect(route('mahasiswa.dashboard'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'fajar.nugraha@univ.ac.id',
            'name' => 'Fajar Nugraha',
            'role' => 'mahasiswa',
        ]);

        $this->assertDatabaseHas('students', [
            'nim' => '210102099',
            'study_program' => 'D4 Rekayasa Perangkat Lunak',
            'batch_year' => 2022,
        ]);

        $this->assertDatabaseHas('companies', [
            'company_name' => 'PT Solusi Teknologi Indonesia',
            'field_supervisor' => 'Ir. Hendri Santoso',
        ]);

        $this->assertDatabaseHas('internships', [
            'status' => 'aktif',
        ]);
    }

    public function test_new_students_can_register_and_input_new_supervisor(): void
    {
        $payload = [
            'name' => 'Dimas Maulana',
            'email' => 'dimas@univ.ac.id',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'nim' => '210102100',
            'study_program' => 'D4 Rekayasa Perangkat Lunak',
            'batch_year' => 2022,
            'phone_number' => '081234567891',
            'company_name' => 'PT Cloud Inovasi Mandiri',
            'company_address' => 'Jakarta Selatan',
            'field_supervisor' => 'Ahmad Fauzi',
            'field_supervisor_position' => 'Senior DevOps',
            'supervisor_name' => 'Prof. Dr. Ir. Wahyu Wibowo, M.Sc.',
            'supervisor_nip' => '197505122001121002',
            'supervisor_position' => 'Guru Besar / Lektor Kepala',
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-31',
        ];

        $response = $this->post('/register', $payload);

        $response->assertRedirect(route('mahasiswa.dashboard'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('supervisors', [
            'employee_number' => '197505122001121002',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Prof. Dr. Ir. Wahyu Wibowo, M.Sc.',
            'role' => 'pembimbing',
        ]);

        $supervisor = \App\Models\Supervisor::where('employee_number', '197505122001121002')->first();
        $this->assertNotNull($supervisor);

        $this->assertDatabaseHas('internships', [
            'supervisor_id' => $supervisor->id,
            'status' => 'aktif',
        ]);
    }

    public function test_registration_fails_with_duplicate_nim(): void
    {
        // First registration
        $this->post('/register', [
            'name' => 'User Satu',
            'email' => 'satu@univ.ac.id',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'nim' => '210102999',
            'study_program' => 'D3 Sistem Informasi',
            'batch_year' => 2022,
        ]);

        Auth::logout();

        // Second registration with same NIM
        $response = $this->post('/register', [
            'name' => 'User Dua',
            'email' => 'dua@univ.ac.id',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'nim' => '210102999',
            'study_program' => 'D3 Sistem Informasi',
            'batch_year' => 2022,
        ]);

        $response->assertSessionHasErrors('nim');
    }
}
