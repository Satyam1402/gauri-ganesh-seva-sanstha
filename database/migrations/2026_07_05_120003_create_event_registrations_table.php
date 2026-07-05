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
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('email', 150);
            $table->string('phone', 20);
            $table->string('city', 100)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            // One registration per email per event — enforced at the DB level
            // in addition to the form request rule.
            $table->unique(['event_id', 'email'], 'uq_event_registrations_event_email');
            $table->index(['event_id', 'status'], 'idx_event_registrations_event_status');
            $table->index('status', 'idx_event_registrations_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
    }
};
