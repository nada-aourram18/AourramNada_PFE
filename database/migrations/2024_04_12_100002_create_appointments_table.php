<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_uid')->unique();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->enum('consultation_type', ['general', 'dentaire', 'autre'])->default('general');
            $table->enum('status', ['confirme', 'en_attente', 'annule'])->default('en_attente');
            $table->string('google_calendar_event_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
