<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_enquiries', function (Blueprint $table) {
            $table->id();

            // Public, non-guessable identifier quoted in emails.
            $table->uuid('reference')->unique();

            $table->string('name', 150);
            $table->string('email', 150)->index();
            $table->string('phone', 20)->nullable();

            $table->string('subject', 200);
            $table->string('category', 30)->index();
            $table->text('message');

            // Consent checkbox stored as a timestamp for an audit trail.
            $table->timestamp('consented_at');

            // Kept for spam triage; IPv6 needs up to 45 characters.
            $table->string('ip_address', 45)->nullable();

            // Triage workflow
            $table->string('status', 20)->default('new')->index();
            $table->text('admin_notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('replied_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Serves the admin default listing (newest per status tab).
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_enquiries');
    }
};
