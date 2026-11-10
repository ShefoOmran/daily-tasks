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
        Schema::table('task_completions', function (Blueprint $table) {
            $table->string('status')->default('approved')->after('completed_at');
            $table->text('submission_text')->nullable()->after('status');
            $table->string('submission_file_path')->nullable()->after('submission_text');
            $table->timestamp('submitted_at')->nullable()->after('submission_file_path');
            $table->timestamp('reviewed_at')->nullable()->after('submitted_at');
            $table->text('review_note')->nullable()->after('reviewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_completions', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'submission_text',
                'submission_file_path',
                'submitted_at',
                'reviewed_at',
                'review_note',
            ]);
        });
    }
};
