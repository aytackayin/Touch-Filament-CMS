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
        Schema::create('touch_files', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('path')->nullable();
            $table->string('type')->nullable(); // image, video, document, archive, etc.
            $table->string('mime_type')->nullable();
            $table->bigInteger('size')->nullable(); // file size in bytes
            $table->foreignId('parent_id')->nullable()->constrained('touch_files')->onDelete('cascade');
            $table->boolean('is_folder')->default(false);
            $table->json('metadata')->nullable(); // additional metadata
            $table->timestamps();

            $table->index('parent_id');
            $table->index('is_folder');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('touch_files');
    }
};
