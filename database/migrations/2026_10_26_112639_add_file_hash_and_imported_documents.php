<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activity_attachments', function (Blueprint $table) {
            $table->string('file_hash', 64)->nullable()->index()->after('file_size');
        });

        Schema::create('imported_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained('internships')->cascadeOnDelete();
            $table->string('file_name', 255);
            $table->string('file_hash', 64)->index();
            $table->bigInteger('file_size')->nullable();
            $table->integer('activities_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imported_documents');

        Schema::table('activity_attachments', function (Blueprint $table) {
            $table->dropColumn('file_hash');
        });
    }
};
