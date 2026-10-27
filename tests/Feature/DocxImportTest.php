<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityAttachment;
use App\Models\Company;
use App\Models\Internship;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocxImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Student $student;
    protected Internship $internship;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->user = User::factory()->create([
            'name' => 'Baraja Putra',
            'role' => 'mahasiswa',
        ]);

        $this->student = Student::create([
            'user_id' => $this->user->id,
            'nim' => '10601006',
            'study_program' => 'D4 TRPL',
            'batch_year' => 2026,
            'phone_number' => '081234567890',
        ]);

        $company = Company::create([
            'company_name' => 'PT Dwidasa Samsara Indonesia',
            'address' => 'Bandung',
            'field_supervisor' => 'Cecep Saefudin',
            'field_supervisor_position' => 'Leader QC',
        ]);

        $this->internship = Internship::create([
            'student_id' => $this->student->id,
            'company_id' => $company->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-12-31',
            'status' => 'aktif',
        ]);
    }

    public function test_student_can_view_import_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('mahasiswa.activities.import'));

        $response->assertStatus(200);
        $response->assertSee('Import Aktivitas dari File Word (.docx)');
        $response->assertSee('Ekstrak');
    }

    public function test_student_can_upload_and_parse_real_docx(): void
    {
        $realDocxPath = base_path('BUKU AKTIVITAS HARIAN ALL DAY.docx');
        $this->assertFileExists($realDocxPath);

        $file = new UploadedFile(
            $realDocxPath,
            'BUKU AKTIVITAS HARIAN ALL DAY.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true
        );

        $response = $this->actingAs($this->user)->post(route('mahasiswa.activities.import.process'), [
            'files' => [$file],
        ]);

        $response->assertRedirect(route('mahasiswa.activities.import.preview'));
        $this->assertTrue(session()->has('pending_docx_import'));

        $importData = session('pending_docx_import');
        $this->assertCount(13, $importData['activities']);
        $this->assertEquals(1, $importData['activities'][0]['day_number']);
        $this->assertEquals('2026-08-03', $importData['activities'][0]['activity_date']);
        $this->assertEquals('QC', $importData['activities'][0]['work_field']);
    }

    public function test_student_can_view_preview_page(): void
    {
        $realDocxPath = base_path('BUKU AKTIVITAS HARIAN ALL DAY.docx');
        $file = new UploadedFile(
            $realDocxPath,
            'BUKU AKTIVITAS HARIAN ALL DAY.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true
        );

        $this->actingAs($this->user)->post(route('mahasiswa.activities.import.process'), [
            'files' => [$file],
        ]);

        $response = $this->actingAs($this->user)->get(route('mahasiswa.activities.import.preview'));

        $response->assertStatus(200);
        $response->assertSee('Pratinjau Hasil Ekstraksi Logbook');
        $response->assertSee('Simpan ke Logbook (Status Draft)');
        $response->assertSee('Hari Ke:');
    }

    public function test_student_can_confirm_import_and_save_to_database(): void
    {
        $realDocxPath = base_path('BUKU AKTIVITAS HARIAN ALL DAY.docx');
        $file = new UploadedFile(
            $realDocxPath,
            'BUKU AKTIVITAS HARIAN ALL DAY.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true
        );

        $this->actingAs($this->user)->post(route('mahasiswa.activities.import.process'), [
            'files' => [$file],
        ]);

        $importData = session('pending_docx_import');
        $token = $importData['token'];
        $activities = $importData['activities'];

        $postPayload = [
            'token' => $token,
            'activities' => $activities,
        ];

        $response = $this->actingAs($this->user)->post(route('mahasiswa.activities.import.confirm'), $postPayload);

        $response->assertRedirect(route('mahasiswa.activities'));
        $response->assertSessionHas('success');

        // Verify activities in database
        $this->assertEquals(13, Activity::where('internship_id', $this->internship->id)->count());

        $day1 = Activity::where('internship_id', $this->internship->id)->where('day_number', 1)->first();
        $this->assertNotNull($day1);
        $this->assertEquals('Draft', $day1->status);
        $this->assertEquals('2026-08-03', $day1->activity_date->format('Y-m-d'));
        $this->assertStringContainsString('setup dengan menginstall aplikasi', $day1->activities);

        // Day 1 has 1 image attachment extracted
        $day1Attachments = ActivityAttachment::where('activity_id', $day1->id)->get();
        $this->assertCount(1, $day1Attachments);
        $this->assertTrue(Storage::disk('public')->exists($day1Attachments->first()->file_path));

        // Session should be cleared
        $this->assertFalse(session()->has('pending_docx_import'));
    }

    public function test_conflict_handling_overwrite_and_skip(): void
    {
        // Pre-create existing activity for day 1
        $existingDay1 = Activity::create([
            'internship_id' => $this->internship->id,
            'day_number' => 1,
            'activity_date' => '2026-08-01',
            'work_field' => 'Old Field',
            'activities' => 'Old Activity Content',
            'tools_used' => 'Old Tools',
            'problems' => 'Old Problems',
            'solutions' => 'Old Solutions',
            'status' => 'Disetujui',
        ]);

        $token = 'test_token';
        Storage::disk('public')->makeDirectory('temp_imports/' . $token);

        $payload = [
            'token' => $token,
            'activities' => [
                [
                    'selected' => '1',
                    'day_number' => 1,
                    'activity_date' => '2026-08-03',
                    'work_field' => 'Updated Field',
                    'activities' => 'Updated Activities Content',
                    'tools_used' => 'Updated Tools',
                    'problems' => 'Updated Problems',
                    'solutions' => 'Updated Solutions',
                    'conflict_action' => 'overwrite',
                    'images' => [],
                ],
                [
                    'selected' => '1',
                    'day_number' => 2,
                    'activity_date' => '2026-08-04',
                    'work_field' => 'New Field Day 2',
                    'activities' => 'New Activities Day 2',
                    'tools_used' => 'New Tools',
                    'problems' => 'None',
                    'solutions' => 'None',
                    'conflict_action' => 'overwrite',
                    'images' => [],
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('mahasiswa.activities.import.confirm'), $payload);
        $response->assertRedirect(route('mahasiswa.activities'));

        // Existing day 1 should be updated and reset to Draft
        $existingDay1->refresh();
        $this->assertEquals('Updated Field', $existingDay1->work_field);
        $this->assertEquals('Updated Activities Content', $existingDay1->activities);
        $this->assertEquals('Draft', $existingDay1->status);

        // Day 2 should be created
        $this->assertDatabaseHas('activities', [
            'internship_id' => $this->internship->id,
            'day_number' => 2,
            'work_field' => 'New Field Day 2',
        ]);
    }

    public function test_cancel_import_cleans_up_session_and_directory(): void
    {
        $token = 'test_cancel_token';
        Storage::disk('public')->makeDirectory('temp_imports/' . $token);
        Storage::disk('public')->put('temp_imports/' . $token . '/dummy.png', 'fake image');

        session(['pending_docx_import' => ['token' => $token, 'activities' => []]]);

        $response = $this->actingAs($this->user)->post(route('mahasiswa.activities.import.cancel'), [
            'token' => $token,
        ]);

        $response->assertRedirect(route('mahasiswa.activities'));
        $this->assertFalse(session()->has('pending_docx_import'));
        $this->assertFalse(Storage::disk('public')->exists('temp_imports/' . $token . '/dummy.png'));
    }

    public function test_duplicate_docx_in_same_batch_is_skipped(): void
    {
        $realDocxPath = base_path('BUKU AKTIVITAS HARIAN ALL DAY.docx');
        $this->assertFileExists($realDocxPath);

        $file1 = new UploadedFile($realDocxPath, 'file1.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
        $file2 = new UploadedFile($realDocxPath, 'file2_copy.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        $response = $this->actingAs($this->user)->post(route('mahasiswa.activities.import.process'), [
            'files' => [$file1, $file2],
        ]);

        $response->assertRedirect(route('mahasiswa.activities.import.preview'));
        $importData = session('pending_docx_import');

        // Only 13 activities extracted (the duplicate file was skipped)
        $this->assertCount(13, $importData['activities']);
        $this->assertEquals(1, $importData['stats']['duplicate_count']);
        $this->assertCount(1, $importData['stats']['skipped_duplicates']);
        $this->assertEquals('file2_copy.docx', $importData['stats']['skipped_duplicates'][0]['file_name']);
    }

    public function test_historical_duplicate_docx_is_detected_and_rejected_when_all_are_duplicates(): void
    {
        $realDocxPath = base_path('BUKU AKTIVITAS HARIAN ALL DAY.docx');
        $fileHash = hash_file('sha256', $realDocxPath);

        // Pre-record this document as already imported for this internship
        \App\Models\ImportedDocument::create([
            'internship_id' => $this->internship->id,
            'file_name' => 'already_imported.docx',
            'file_hash' => $fileHash,
            'file_size' => filesize($realDocxPath),
            'activities_count' => 13,
        ]);

        $file = new UploadedFile($realDocxPath, 'reupload.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        $response = $this->actingAs($this->user)->from(route('mahasiswa.activities.import'))->post(route('mahasiswa.activities.import.process'), [
            'files' => [$file],
        ]);

        $response->assertRedirect(route('mahasiswa.activities.import'));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('terdeteksi duplikat', session('error'));
    }

    public function test_duplicate_activity_attachments_are_deduplicated(): void
    {
        $content = "%PDF-1.4\nidentical image payload content";
        $file1 = UploadedFile::fake()->createWithContent('foto1.pdf', $content);
        $file2 = UploadedFile::fake()->createWithContent('foto1_copy.pdf', $content);

        $response = $this->actingAs($this->user)->post(route('mahasiswa.activities.store'), [
            'day_number' => 10,
            'activity_date' => '2026-08-20',
            'work_field' => 'Engineering',
            'activities' => 'Testing duplicate attachment upload',
            'action_type' => 'draft',
            'attachments' => [$file1, $file2],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('mahasiswa.activities'));
        $response->assertSessionHas('success');

        $activity = Activity::where('internship_id', $this->internship->id)->where('day_number', 10)->first();
        $this->assertNotNull($activity);

        // Only 1 attachment record should exist because the 2nd was identical
        $this->assertEquals(1, $activity->attachments()->count());
        $this->assertNotNull($activity->attachments()->first()->file_hash);
    }

    public function test_student_can_upload_and_parse_pdf_logbook(): void
    {
        $html = '<html><body>
            <h2>Hari Ke-1, Tanggal 5 Agustus 2026</h2>
            <table>
                <tr>
                    <td>Mobile Development</td>
                    <td>Memperbaiki bug navigasi dan login auth</td>
                    <td>Tools: Flutter, Android Studio. Masalah: Crash di Android 14. Solusi: Update target sdk di build.gradle</td>
                </tr>
            </table>
        </body></html>';

        $pdfBinary = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->output();
        $file = UploadedFile::fake()->createWithContent('logbook_pribadi.pdf', $pdfBinary);

        $response = $this->actingAs($this->user)->post(route('mahasiswa.activities.import.process'), [
            'files' => [$file],
        ]);

        $response->assertRedirect(route('mahasiswa.activities.import.preview'));
        $this->assertTrue(session()->has('pending_docx_import'));

        $importData = session('pending_docx_import');
        $this->assertCount(1, $importData['activities']);
        $this->assertEquals(1, $importData['activities'][0]['day_number']);
        $this->assertEquals('2026-08-05', $importData['activities'][0]['activity_date']);
        $this->assertEquals('Mobile Development', $importData['activities'][0]['work_field']);
        $this->assertStringContainsString('Memperbaiki bug navigasi', $importData['activities'][0]['activities']);

        // Verify preview page renders successfully without any Undefined array key error
        $previewResponse = $this->actingAs($this->user)->get(route('mahasiswa.activities.import.preview'));
        $previewResponse->assertStatus(200);
        $previewResponse->assertSee('Mobile Development');
    }

    public function test_student_can_upload_both_docx_and_pdf_in_same_batch(): void
    {
        $realDocxPath = base_path('BUKU AKTIVITAS HARIAN ALL DAY.docx');
        $this->assertFileExists($realDocxPath);
        $docxFile = new UploadedFile($realDocxPath, 'file1.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        $html = '<html><body>
            <h2>Hari Ke-20, Tanggal 28 Agustus 2026</h2>
            <table>
                <tr>
                    <td>DevOps Support</td>
                    <td>Deployment container docker ke server staging</td>
                    <td>Tools: Docker, Portainer. Masalah: Port conflict 8080. Solusi: Mapping ke port 8081</td>
                </tr>
            </table>
        </body></html>';
        $pdfBinary = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->output();
        $pdfFile = UploadedFile::fake()->createWithContent('extra_day.pdf', $pdfBinary);

        $response = $this->actingAs($this->user)->post(route('mahasiswa.activities.import.process'), [
            'files' => [$docxFile, $pdfFile],
        ]);

        $response->assertRedirect(route('mahasiswa.activities.import.preview'));
        $importData = session('pending_docx_import');

        // 13 from DOCX + 1 from PDF = 14 activities
        $this->assertCount(14, $importData['activities']);

        $day20 = collect($importData['activities'])->firstWhere('day_number', 20);
        $this->assertNotNull($day20);
        $this->assertEquals('DevOps Support', $day20['work_field']);
        $this->assertEquals('extra_day.pdf', $day20['source_file']);
    }

    public function test_student_can_attach_new_images_during_import_confirmation(): void
    {
        $token = 'test_token_img';
        Storage::disk('public')->makeDirectory('temp_imports/' . $token);

        $newImage = UploadedFile::fake()->image('bukti_kegiatan_baru.png', 400, 300);

        $payload = [
            'token' => $token,
            'activities' => [
                [
                    'selected' => '1',
                    'day_number' => 5,
                    'activity_date' => '2026-08-07',
                    'work_field' => 'Backend Development',
                    'activities' => 'Implementasi REST API endpoint',
                    'tools_used' => 'Laravel, Postman',
                    'problems' => 'None',
                    'solutions' => 'None',
                    'conflict_action' => 'overwrite',
                    'images' => [],
                ],
            ],
            'new_activity_images' => [
                0 => [$newImage],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('mahasiswa.activities.import.confirm'), $payload);
        $response->assertRedirect(route('mahasiswa.activities'));

        $activity = Activity::where('internship_id', $this->internship->id)->where('day_number', 5)->first();
        $this->assertNotNull($activity);

        // Attachment should be saved
        $attachments = $activity->attachments;
        $this->assertCount(1, $attachments);
        $this->assertEquals('bukti_kegiatan_baru.png', $attachments->first()->file_name);
        $this->assertTrue(Storage::disk('public')->exists($attachments->first()->file_path));
    }

    public function test_pdf_with_extracted_images_renders_preview_page_without_error(): void
    {
        $token = 'test_pdf_img_token';
        Storage::disk('public')->makeDirectory('temp_imports/' . $token);
        Storage::disk('public')->put('temp_imports/' . $token . '/img_pdf_123.jpg', 'fake_jpg_content');

        session([
            'pending_docx_import' => [
                'token' => $token,
                'stats' => [
                    'total_extracted' => 1,
                    'conflict_count' => 0,
                    'image_count' => 1,
                    'skipped_duplicates' => [],
                ],
                'activities' => [
                    [
                        'day_number' => 1,
                        'activity_date' => '2026-08-03',
                        'work_field' => 'Quality Assurance',
                        'activities' => 'Testing modul autentikasi',
                        'tools_used' => 'Postman',
                        'problems' => 'None',
                        'solutions' => 'None',
                        'is_conflict' => false,
                        'conflict_action' => 'overwrite',
                        'source_file' => 'logbook.pdf',
                        'images' => [
                            [
                                'file_name' => 'img_pdf_123.jpg',
                                'temp_path' => 'temp_imports/' . $token . '/img_pdf_123.jpg',
                                'preview_url' => asset('storage/temp_imports/' . $token . '/img_pdf_123.jpg'),
                                'file_type' => 'jpg',
                                'file_size' => 1024,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($this->user)->get(route('mahasiswa.activities.import.preview'));
        $response->assertStatus(200);
        $response->assertSee('Quality Assurance');
        $response->assertSee('img_pdf_123.jpg');
    }
}
