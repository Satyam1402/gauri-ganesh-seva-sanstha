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
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faq_category_id')->nullable()
                ->constrained('faq_categories')->nullOnDelete();
            $table->string('question', 255);
            // Markdown source; rendered with HTML stripped (see Faq::answerHtml).
            $table->text('answer');
            $table->string('status', 20)->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_featured', 'display_order'], 'idx_faqs_status_featured_order');
            $table->index(['faq_category_id', 'status', 'display_order'], 'idx_faqs_category_status_order');
            $table->index('published_at', 'idx_faqs_published_at');
        });

        // Natural-language search on MySQL avoids leading-wildcard LIKE scans;
        // SQLite (tests) falls back to LIKE in the repository.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('faqs', function (Blueprint $table) {
                $table->fullText(['question', 'answer'], 'ft_faqs_question_answer');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
