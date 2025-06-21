<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('file_versions', function (Blueprint $table) {
            $table->id();
            $table->string('description')->nullable();
            $table->string('type'); // e.g., 'major', 'minor', 'patch'
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('version_number');
            $table->boolean('is_current_version')->default(false);
            $table->boolean('is_public')->default(false);
            $table->foreignId('file_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Indexes
            $table->index('file_id');
            $table->index('version_number');
            $table->index('is_current_version');
            $table->index('is_public');
            
            // Unique constraint to ensure only one current version per file
            $table->unique(['file_id', 'is_current_version']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fileVersions');
    }
};
