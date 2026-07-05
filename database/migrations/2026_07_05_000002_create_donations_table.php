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
        Schema::create('donations', function (Blueprint $table) {
            $table->id();

            // Public, non-guessable identifier used in success/failure URLs so
            // donor details are never exposed behind a sequential id.
            $table->uuid('reference')->unique();

            $table->foreignId('donation_campaign_id')->nullable()
                ->constrained('donation_campaigns')->nullOnDelete();

            $table->string('donor_name', 150);
            $table->string('donor_email', 150);
            $table->string('donor_phone', 20)->nullable();
            $table->string('donor_address', 500)->nullable();
            $table->string('pan_number', 10)->nullable();

            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('INR');
            $table->string('payment_method', 30);
            $table->string('transaction_id', 191)->nullable();
            $table->string('payment_status', 20)->default('pending');
            $table->boolean('is_anonymous')->default(false);
            $table->string('receipt_number', 40)->nullable()->unique();
            $table->dateTime('donated_at');
            $table->text('remarks')->nullable();

            // Gateway payloads (order ids, signatures, raw responses).
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['payment_status', 'donated_at'], 'idx_donations_status_date');
            $table->index(['donation_campaign_id', 'payment_status'], 'idx_donations_campaign_status');
            $table->index('donor_email', 'idx_donations_donor_email');
            $table->index('transaction_id', 'idx_donations_transaction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
