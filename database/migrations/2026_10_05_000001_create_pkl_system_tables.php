<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nim', 50)->unique();
            $table->string('study_program', 150);
            $table->integer('batch_year')->nullable();
            $table->string('phone_number', 30)->nullable();
            $table->timestamps();
        });

        Schema::create('supervisors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('employee_number', 50)->unique();
            $table->string('position', 150)->nullable();
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 255);
            $table->text('address')->nullable();
            $table->string('field_supervisor', 150)->nullable();
            $table->string('field_supervisor_position', 150)->nullable();
            $table->timestamps();
        });

        Schema::create('internships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('supervisors')->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 50)->default('aktif');
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained('internships')->cascadeOnDelete();
            $table->integer('day_number');
            $table->date('activity_date');
            $table->string('work_field', 150);
            $table->text('activities');
            $table->text('tools_used')->nullable();
            $table->text('problems')->nullable();
            $table->text('solutions')->nullable();
            $table->string('status', 50)->default('Draft');
            $table->timestamps();
        });

        Schema::create('activity_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->string('file_name', 255);
            $table->text('file_path');
            $table->string('file_type', 50)->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->timestamps();
        });

        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->constrained('supervisors')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->string('status', 50)->default('Disetujui');
            $table->timestamp('approved_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained('internships')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('supervisors')->nullOnDelete();
            $table->decimal('attendance_score', 5, 2)->default(0);
            $table->decimal('discipline_score', 5, 2)->default(0);
            $table->decimal('activity_score', 5, 2)->default(0);
            $table->decimal('report_score', 5, 2)->default(0);
            $table->decimal('final_score', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
        Schema::dropIfExists('approvals');
        Schema::dropIfExists('activity_attachments');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('internships');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('supervisors');
        Schema::dropIfExists('students');
    }
};
