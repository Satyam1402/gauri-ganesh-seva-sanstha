<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replies form a thread under an enquiry so the whole conversation
     * history stays auditable — not just the latest response.
     */
    public function up(): void
    {
        Schema::create('contact_enquiry_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_enquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_enquiry_replies');
    }
};
