<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('volunteer_applications', function (Blueprint $table) {
            $table->id();

            // Public, non-guessable identifier used in emails so applicants can
            // quote it without exposing the auto-increment id.
            $table->uuid('reference')->unique();

            // Personal details
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('gender', 30);
            $table->date('date_of_birth');

            // Contact
            $table->string('email', 150)->index();
            $table->string('phone', 20);
            $table->string('alternate_phone', 20)->nullable();

            // Address
            $table->string('address', 500);
            $table->string('city', 100)->index();
            $table->string('state', 100);
            $table->string('country', 100)->default('India');
            $table->string('pin_code', 12);

            // Professional background
            $table->string('occupation', 150);
            $table->string('organization', 150)->nullable();
            $table->text('skills');
            $table->text('experience')->nullable();

            // Volunteering preferences
            $table->json('areas_of_interest');
            $table->string('availability', 30)->index();

            // Safety & wellbeing
            $table->string('emergency_contact_name', 150);
            $table->string('emergency_contact_phone', 20);
            $table->text('medical_information')->nullable();

            $table->text('message')->nullable();
            $table->string('preferred_communication_method', 30);

            // Consent checkbox is stored as a timestamp — a better audit trail
            // than a boolean since it records *when* consent was given.
            $table->timestamp('consented_at');

            // Review workflow
            $table->string('status', 30)->default('pending')->index();
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Serves the admin default listing (newest per status tab).
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('volunteer_applications');
    }
};
