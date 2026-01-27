<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_url')->nullable()->after('email');
            $table->string('phone')->nullable()->after('avatar_url');
            $table->text('address')->nullable()->after('phone');
            $table->json('social_links')->nullable()->after('address');
            $table->string('default_editor')->default('richtext')->after('social_links');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar_url', 'phone', 'address', 'social_links', 'default_editor']);
        });
    }
};
