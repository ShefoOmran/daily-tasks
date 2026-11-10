<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')->where('is_admin', true)->update(['subscription_id' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
