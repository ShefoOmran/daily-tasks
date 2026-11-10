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
            $table->foreignId('task_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->timestamp('started_at')->nullable()->after('task_id');
            $table->unsignedSmallInteger('task_duration_minutes')->nullable()->after('started_at');
            $table->timestamp('completed_at')->nullable()->change();
        });

        DB::table('task_completions')->whereNull('started_at')->update([
            'started_at' => DB::raw('completed_at'),
            'task_duration_minutes' => 0,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_completions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('task_id');
            $table->dropColumn(['started_at', 'task_duration_minutes']);
            $table->timestamp('completed_at')->nullable(false)->change();
        });
    }
};
