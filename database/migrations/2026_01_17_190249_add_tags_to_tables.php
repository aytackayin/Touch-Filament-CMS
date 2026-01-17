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
        Schema::table('blogs', function (Blueprint $table) {
            $table->json('tags')->nullable();
        });

        Schema::table('blog_categories', function (Blueprint $table) {
            $table->json('tags')->nullable();
        });

        Schema::table('touch_files', function (Blueprint $table) {
            $table->json('tags')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn('tags');
        });

        Schema::table('blog_categories', function (Blueprint $table) {
            $table->dropColumn('tags');
        });

        Schema::table('touch_files', function (Blueprint $table) {
            $table->dropColumn('tags');
        });
    }
};
