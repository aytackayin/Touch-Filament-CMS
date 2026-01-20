<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('touch_files', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->onDelete('set null');
            $table->foreignId('edit_user_id')->nullable()->after('user_id')->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('touch_files', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['edit_user_id']);
            $table->dropColumn(['user_id', 'edit_user_id']);
        });
    }
};
